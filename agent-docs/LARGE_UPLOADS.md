# Large File Upload Configuration Guide

This guide documents the setup required for handling large file uploads (100MB+) in Larchive, including audio, video, and other media files.

## Table of Contents

1. [Overview](#overview)
2. [PHP Configuration](#php-configuration)
3. [Web Server Configuration](#web-server-configuration)
4. [Storage Configuration](#storage-configuration)
5. [Queue Configuration](#queue-configuration)
6. [Application Configuration](#application-configuration)
7. [Deployment Checklist](#deployment-checklist)
8. [Troubleshooting](#troubleshooting)

---

## Overview

Larchive handles large file uploads through:

1. **Streamed Uploads**: Files are streamed directly to object storage without loading into memory
2. **Object Storage (S3)**: Primary storage for media files, supporting unlimited file sizes
3. **Queued Processing**: Heavy operations (metadata extraction, transcoding) run asynchronously
4. **Status Tracking**: Processing status displayed to users (`uploading` → `processing` → `ready`)

### Upload Flow

```
User uploads file → FormRequest validation → Stream to S3 → Create Media record (status: uploaded)
                                                            ↓
                                         Dispatch ProcessMediaUpload job → Extract metadata
                                                            ↓
                                              Update Media record (status: ready)
```

---

## PHP Configuration

### Required PHP Settings

Edit your `php.ini` file (or use `.user.ini` in shared hosting):

```ini
; Maximum file upload size (should match or exceed your target file size)
upload_max_filesize = 200M

; Maximum POST body size (should be slightly larger than upload_max_filesize)
; Accounts for multipart form data overhead
post_max_size = 210M

; Maximum execution time for upload requests (in seconds)
; Uploads stream to S3, so this doesn't need to be huge
; But allow time for initial validation and S3 connection
max_execution_time = 300

; Maximum memory per script (queued jobs need memory for getID3)
; Not critical for uploads since we stream, but jobs need it
memory_limit = 256M

; Maximum file uploads per request
max_file_uploads = 20
```

### PHP-FPM Configuration (Production)

Edit `/etc/php/8.3/fpm/pool.d/www.conf` (path may vary):

```ini
; Increase request timeout
request_terminate_timeout = 300

; Ensure sufficient child processes for concurrent uploads
pm.max_children = 50
pm.start_servers = 10
pm.min_spare_servers = 5
pm.max_spare_servers = 20
```

Restart PHP-FPM after changes:

```bash
sudo systemctl restart php8.3-fpm
```

### Verifying PHP Configuration

Create a test route or run in artisan tinker:

```php
php artisan tinker
>>> ini_get('upload_max_filesize');
=> "200M"
>>> ini_get('post_max_size');
=> "210M"
>>> ini_get('max_execution_time');
=> "300"
```

---

## Web Server Configuration

### Nginx

Edit your Nginx site configuration (e.g., `/etc/nginx/sites-available/larchive`):

```nginx
server {
    listen 80;
    server_name larchive.example.com;
    root /var/www/larchive/public;

    index index.php;

    # Increase client body size to match PHP settings
    # Should be slightly larger than post_max_size
    client_max_body_size 210M;

    # Increase timeouts for large uploads
    client_body_timeout 300s;
    client_header_timeout 300s;
    send_timeout 300s;

    # Buffering settings for large files
    client_body_buffer_size 128k;
    
    # If using X-Accel-Redirect for serving files from S3
    # (not needed if serving directly from S3 with signed URLs)
    proxy_buffering off;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;

        # Increase FastCGI timeouts to match PHP settings
        fastcgi_read_timeout 300s;
        fastcgi_send_timeout 300s;

        # Buffer settings
        fastcgi_buffer_size 128k;
        fastcgi_buffers 256 16k;
        fastcgi_busy_buffers_size 256k;
    }
}
```

Test and reload Nginx:

```bash
sudo nginx -t
sudo systemctl reload nginx
```

### Apache

Edit your Apache site configuration or `.htaccess`:

```apache
# Increase timeout
Timeout 300

# PHP settings (if not using php.ini)
php_value upload_max_filesize 200M
php_value post_max_size 210M
php_value max_execution_time 300
php_value memory_limit 256M

# Limit request body size (requires mod_reqtimeout)
<IfModule mod_reqtimeout.c>
    RequestReadTimeout header=300,MinRate=500 body=300,MinRate=500
</IfModule>

# Enable mod_proxy for large uploads
<IfModule mod_proxy.c>
    ProxyTimeout 300
</IfModule>
```

---

## Storage Configuration

### S3 / Object Storage Setup

#### Required Environment Variables

Add to your `.env` file:

```bash
# Storage Configuration
MEDIA_STORAGE_DISK=archives
MEDIA_STORAGE_DRIVER=s3

# AWS S3 Credentials
AWS_ACCESS_KEY_ID=your-access-key-id
AWS_SECRET_ACCESS_KEY=your-secret-access-key
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=larchive-media-production

# Optional: For S3-compatible services (e.g., DigitalOcean Spaces, Wasabi, MinIO)
AWS_ENDPOINT=https://nyc3.digitaloceanspaces.com
AWS_USE_PATH_STYLE_ENDPOINT=false

# Optional: CloudFront or CDN URL for serving files
AWS_URL=https://cdn.larchive.example.com
```

#### S3 Bucket Configuration

1. **Create Bucket**: Create a dedicated bucket for media storage
2. **Block Public Access**: Enable (files are private by default)
3. **CORS Configuration** (if uploading directly from browser in the future):

```json
[
  {
    "AllowedHeaders": ["*"],
    "AllowedMethods": ["GET", "PUT", "POST", "DELETE"],
    "AllowedOrigins": ["https://larchive.example.com"],
    "ExposeHeaders": ["ETag"],
    "MaxAgeSeconds": 3000
  }
]
```

4. **Lifecycle Rules**: Optional - archive old files to Glacier after X days

#### S3-Compatible Services

**DigitalOcean Spaces**:
```bash
AWS_ENDPOINT=https://nyc3.digitaloceanspaces.com
AWS_BUCKET=larchive-media
AWS_DEFAULT_REGION=nyc3
```

**Wasabi**:
```bash
AWS_ENDPOINT=https://s3.us-east-1.wasabisys.com
AWS_BUCKET=larchive-media
AWS_DEFAULT_REGION=us-east-1
```

**MinIO (Self-hosted)**:
```bash
AWS_ENDPOINT=http://minio.yourdomain.com:9000
AWS_USE_PATH_STYLE_ENDPOINT=true
AWS_BUCKET=larchive-media
```

### Local Development Storage

For local development, use the `public` disk instead of S3:

```bash
# .env.local
MEDIA_STORAGE_DISK=public
MEDIA_STORAGE_DRIVER=local
```

Run `php artisan storage:link` to create the symbolic link.

---

## Queue Configuration

Large file processing **requires** a queue system. Do not use the `sync` driver in production.

### Recommended: Redis Queue

1. **Install Redis**:
```bash
sudo apt-get install redis-server
composer require predis/predis
```

2. **Configure .env**:
```bash
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

3. **Start Queue Worker**:
```bash
php artisan queue:work redis --queue=media,default --tries=3 --timeout=600
```

### Alternative: Database Queue

```bash
QUEUE_CONNECTION=database
```

Run migrations:
```bash
php artisan queue:table
php artisan migrate
```

Start worker:
```bash
php artisan queue:work database --queue=media,default --tries=3 --timeout=600
```

### Production Queue Workers

#### Using Supervisor (Recommended)

Create `/etc/supervisor/conf.d/larchive-worker.conf`:

```ini
[program:larchive-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/larchive/artisan queue:work redis --queue=media,default --sleep=3 --tries=3 --max-time=3600 --timeout=600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/larchive/storage/logs/worker.log
stopwaitsecs=3600
```

Start supervisor:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start larchive-worker:*
```

#### Using Laravel Cloud / Vapor

Queue workers are automatically configured. Ensure your `vapor.yml` includes:

```yaml
environments:
  production:
    queues:
      - queue: media
        tries: 3
        timeout: 600
        processes: 2
      - queue: default
        tries: 3
        timeout: 90
```

#### Using Systemd

Create `/etc/systemd/system/larchive-worker@.service`:

```ini
[Unit]
Description=Larchive Queue Worker %i
After=network.target

[Service]
Type=simple
User=www-data
Group=www-data
WorkingDirectory=/var/www/larchive
ExecStart=/usr/bin/php artisan queue:work redis --queue=media,default --sleep=3 --tries=3 --timeout=600
Restart=always
RestartSec=10

[Install]
WantedBy=multi-user.target
```

Enable and start:
```bash
sudo systemctl enable larchive-worker@{1..2}
sudo systemctl start larchive-worker@{1..2}
```

---

## Application Configuration

### Media Configuration (`config/media.php`)

Review and adjust these settings:

```php
return [
    // Maximum upload size in megabytes
    'max_upload_size_mb' => env('MEDIA_MAX_UPLOAD_SIZE_MB', 200),

    // Storage disk for media files
    'disk' => env('MEDIA_STORAGE_DISK', 'archives'),

    // Enable queued processing
    'processing' => [
        'enabled' => env('MEDIA_PROCESSING_ENABLED', true),
        'queue' => env('MEDIA_PROCESSING_QUEUE', 'media'),
    ],

    // Allowed MIME types (add more as needed)
    'allowed_mime_types' => [
        'audio/mpeg', 'audio/mp3', 'audio/wav', 'audio/x-wav',
        'video/mp4', 'video/mpeg', 'video/quicktime', 'video/x-msvideo',
        'image/jpeg', 'image/png', 'image/gif', 'image/webp',
        'application/pdf', // ... etc
    ],
];
```

### Environment Variables Summary

Required `.env` settings for production:

```bash
# Queue
QUEUE_CONNECTION=redis

# Storage
MEDIA_STORAGE_DISK=archives
AWS_ACCESS_KEY_ID=xxx
AWS_SECRET_ACCESS_KEY=xxx
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=larchive-media

# Media Processing
MEDIA_MAX_UPLOAD_SIZE_MB=200
MEDIA_PROCESSING_ENABLED=true
MEDIA_PROCESSING_QUEUE=media
```

---

## Deployment Checklist

Before deploying large file upload capability:

### Infrastructure

- [ ] PHP 8.3+ installed with required extensions (`gd`, `fileinfo`, `mbstring`)
- [ ] `upload_max_filesize` set to 200M (or desired limit)
- [ ] `post_max_size` set to 210M (slightly higher than upload limit)
- [ ] `max_execution_time` set to 300 seconds
- [ ] Web server `client_max_body_size` set to 210M (Nginx) or equivalent
- [ ] Web server timeouts increased to 300 seconds

### Storage

- [ ] S3 bucket created and configured
- [ ] AWS credentials added to `.env`
- [ ] `MEDIA_STORAGE_DISK=archives` in `.env`
- [ ] Bucket CORS configured (if needed)
- [ ] Test file upload and download from S3

### Queue System

- [ ] Redis installed and running (or database queue configured)
- [ ] `QUEUE_CONNECTION=redis` in `.env`
- [ ] Queue worker running (`queue:work`)
- [ ] Supervisor/systemd configured for auto-restart
- [ ] Separate `media` queue with higher timeout (600s)

### Application

- [ ] `composer install --no-dev --optimize-autoloader`
- [ ] `php artisan config:cache`
- [ ] `php artisan route:cache`
- [ ] `php artisan view:cache`
- [ ] Database migration run (`add_processing_status_to_media_table`)
- [ ] getID3 library installed (`james-heinrich/getid3`)

### Testing

- [ ] Upload a small file (< 10MB) - should succeed immediately
- [ ] Upload a large file (> 100MB) - should succeed and show "processing" status
- [ ] Check queue worker logs - job should process successfully
- [ ] Verify metadata extracted (duration, resolution, etc.)
- [ ] Test upload failure handling (invalid file type, oversized file)

### Monitoring

- [ ] Log rotation configured (`/storage/logs`)
- [ ] Queue monitoring dashboard (Laravel Horizon recommended)
- [ ] Failed job alerts configured
- [ ] Disk space monitoring on S3 bucket

---

## Troubleshooting

### Upload Fails with "Request Entity Too Large" (413)

**Cause**: Nginx `client_max_body_size` is too small.

**Solution**:
```nginx
client_max_body_size 210M;
```
Reload Nginx: `sudo systemctl reload nginx`

---

### Upload Fails with "Maximum execution time exceeded"

**Cause**: PHP timeout too low or file processing taking too long.

**Solution**:
- Increase `max_execution_time` in `php.ini`
- Ensure heavy processing is in queued jobs, not synchronous
- Check if `MEDIA_PROCESSING_ENABLED=true` to use queues

---

### Upload Succeeds but Media Status Stuck on "uploaded"

**Cause**: Queue worker not running or job failing silently.

**Solution**:
1. Check if queue worker is running:
   ```bash
   ps aux | grep "queue:work"
   ```
2. Check failed jobs:
   ```bash
   php artisan queue:failed
   ```
3. Check worker logs:
   ```bash
   tail -f storage/logs/worker.log
   ```
4. Retry failed jobs:
   ```bash
   php artisan queue:retry all
   ```

---

### Media Processing Fails with getID3 Error

**Cause**: getID3 library not installed or file format not supported.

**Solution**:
1. Verify getID3 is installed:
   ```bash
   composer show james-heinrich/getid3
   ```
2. Check processing error in database:
   ```sql
   SELECT id, filename, processing_status, processing_error 
   FROM media 
   WHERE processing_status = 'failed';
   ```
3. Retry job manually:
   ```php
   $media = Media::find(123);
   ProcessMediaUpload::dispatch($media);
   ```

---

### S3 Upload Fails with "Access Denied"

**Cause**: Invalid AWS credentials or insufficient permissions.

**Solution**:
1. Verify credentials in `.env`
2. Test AWS credentials:
   ```bash
   php artisan tinker
   >>> Storage::disk('archives')->put('test.txt', 'Hello World');
   => "test.txt"
   >>> Storage::disk('archives')->get('test.txt');
   => "Hello World"
   ```
3. Ensure IAM user has permissions:
   ```json
   {
     "Version": "2012-10-17",
     "Statement": [
       {
         "Effect": "Allow",
         "Action": [
           "s3:PutObject",
           "s3:GetObject",
           "s3:DeleteObject",
           "s3:ListBucket"
         ],
         "Resource": [
           "arn:aws:s3:::larchive-media/*",
           "arn:aws:s3:::larchive-media"
         ]
       }
     ]
   }
   ```

---

### Large File Upload Works Locally but Not in Production

**Checklist**:
1. PHP settings may differ between environments - check with `phpinfo()`
2. Nginx settings may not be applied - check actual config with `nginx -T`
3. Queue worker may not be running in production
4. S3 credentials may be incorrect or missing in production `.env`
5. Firewall may be blocking large POST requests

**Debug Steps**:
```bash
# Check PHP settings in production
php -i | grep upload_max_filesize
php -i | grep post_max_size

# Check Nginx config
sudo nginx -T | grep client_max_body_size

# Check queue worker status
sudo supervisorctl status larchive-worker:*

# Test S3 connection
php artisan tinker
>>> Storage::disk('archives')->exists('test.txt')
```

---

### Memory Limit Exceeded in Queue Jobs

**Cause**: Processing very large video files with getID3 can consume significant memory.

**Solution**:
1. Increase `memory_limit` for queue workers specifically:
   ```bash
   php -d memory_limit=512M artisan queue:work
   ```
2. Update supervisor config:
   ```ini
   command=php -d memory_limit=512M /var/www/larchive/artisan queue:work ...
   ```
3. For extremely large files, consider using ffmpeg instead of getID3

---

## Performance Optimization

### Recommendations for Large Deployments

1. **Use CloudFront or CDN**: Serve media files through a CDN with signed URLs
2. **Enable S3 Transfer Acceleration**: For global users uploading large files
3. **Use Laravel Horizon**: Better queue monitoring and management
4. **Implement Chunked Uploads**: For files > 1GB, use client-side chunking
5. **Add Progress Tracking**: Use websockets or polling for real-time upload progress
6. **Compress Thumbnails**: Generate optimized thumbnails for video/image previews
7. **Use SQS for Queues**: More reliable than Redis for production at scale

### Scaling Queue Workers

Adjust worker count based on upload volume:
- **Low traffic** (< 10 uploads/day): 1 worker
- **Medium traffic** (10-100 uploads/day): 2-3 workers
- **High traffic** (> 100 uploads/day): 5+ workers

Monitor queue depth:
```bash
php artisan queue:size media
```

---

## Additional Resources

- [Laravel File Storage Documentation](https://laravel.com/docs/11.x/filesystem)
- [Laravel Queue Documentation](https://laravel.com/docs/11.x/queues)
- [getID3 Documentation](https://github.com/JamesHeinrich/getID3)
- [AWS S3 Best Practices](https://docs.aws.amazon.com/AmazonS3/latest/userguide/Welcome.html)

---

**Last Updated**: 2025-11-20  
**Larchive Version**: 1.0  
**Author**: Development Team
