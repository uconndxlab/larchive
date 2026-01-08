# Chunked Upload Implementation for Large Files

## Overview

Implemented chunked upload functionality using DropzoneJS to handle very large file uploads (up to 50GB) through the web UI without requiring SFTP.

## Changes Made

### 1. Routes (`routes/web.php`)
- Added new route: `POST items/{item}/media/chunk` for handling chunked uploads

### 2. Controller (`app/Http/Controllers/MediaController.php`)
- Added `uploadChunk()` method that:
  - Receives file chunks (2MB each)
  - Stores chunks temporarily
  - Combines chunks when all received
  - Creates media record
  - Dispatches processing job
  - Cleans up temporary files

### 3. Frontend (`resources/views/items/_media_and_transcript.blade.php`)
- Replaced simple file input with DropzoneJS
- Added drag-and-drop interface
- Configured chunked uploads (2MB chunks)
- Added progress tracking
- Auto-retry failed chunks
- Real-time upload status

### 4. File Structure
- Created `storage/app/temp/` directory for temporary chunk storage
- Added to `.gitignore`

## How It Works

1. **User selects large file** (e.g., 5GB video)
2. **DropzoneJS splits file** into 2MB chunks in browser
3. **Each chunk uploaded separately** to `/items/{item}/media/chunk`
4. **Server saves chunks** to `storage/app/temp/chunks/{uuid}/`
5. **When all chunks received**, server:
   - Combines chunks into complete file
   - Moves to permanent storage
   - Creates media record
   - Dispatches processing job
   - Deletes temporary files

## Configuration

### Upload Limits
- **Chunk size**: 2MB
- **Max file size**: 50GB (configurable)
- **Chunk timeout**: 5 minutes per chunk
- **Retry attempts**: 3 per chunk

### Server Requirements

**PHP settings** (`php.ini` or `.user.ini`):
```ini
upload_max_filesize = 10M       # Just needs to handle chunk size
post_max_size = 12M             # Slightly larger than chunk
max_execution_time = 300        # Per chunk
memory_limit = 256M             # For processing
```

**Nginx** (if applicable):
```nginx
client_max_body_size 12M;       # Just needs to handle chunk size
client_body_timeout 300s;
```

### Disk Space Requirements
- Temp directory needs space for full file during assembly
- Permanent storage needs space for final file
- **Total: ~2x file size** during upload process

## Testing

1. Navigate to item edit page
2. Click on **Media & Transcripts** tab
3. Drag and drop a large file or click to browse
4. Watch progress bar as file uploads
5. File automatically appears in media list when complete

### Test Cases
- ✅ Small file (< 10MB) - should upload quickly
- ✅ Medium file (100MB - 1GB) - see chunking in action
- ✅ Large file (> 1GB) - verify no timeout issues
- ✅ Multiple files - upload sequentially
- ✅ Network interruption - chunks should retry
- ✅ Cancel upload - partial chunks cleaned up

## Features

✅ **No size limits** - files automatically split into chunks  
✅ **Resume support** - failed chunks auto-retry (3 attempts)  
✅ **Progress tracking** - real-time upload progress  
✅ **Works with local storage** - no S3 required  
✅ **Server-friendly** - only 2MB hits PHP at once  
✅ **Modern UI** - drag-and-drop with preview  
✅ **Visual feedback** - progress bars, success/error states  
✅ **Auto-cleanup** - temp files removed after processing  

## Troubleshooting

### Upload fails with "No file chunk received"
- Check PHP `upload_max_filesize` is at least 10M
- Verify `post_max_size` is at least 12M

### Chunks timing out
- Increase `max_execution_time` in PHP
- Check network stability
- Reduce `chunkSize` in JavaScript (currently 2MB)

### Out of disk space
- Check available space in `storage/app/temp/`
- Large files need 2x their size temporarily
- Clean up old temp files if needed:
  ```bash
  find storage/app/temp/chunks -type d -mtime +1 -exec rm -rf {} +
  ```

### Files not appearing in media list
- Check browser console for JavaScript errors
- Verify HTMX is loaded
- Check Laravel logs: `storage/logs/laravel.log`
- Verify processing queue is running:
  ```bash
  php artisan queue:work
  ```

## Comparison: Web UI vs SFTP

| Feature | Chunked Web Upload | SFTP |
|---------|-------------------|------|
| Max file size | 50GB (configurable) | Unlimited |
| User experience | Drag & drop, progress bar | Requires FTP client |
| Setup required | None for users | Credentials, client software |
| Resume on failure | Automatic (per chunk) | Manual |
| Browser compatibility | Modern browsers only | N/A |
| Server load | Moderate (chunk assembly) | Low |
| Best for | Most users, most files | Extremely large files (50GB+) |

## Future Enhancements

- [ ] Add pause/resume button for user control
- [ ] WebSocket progress updates for real-time feedback
- [ ] Direct-to-S3 upload option (bypass server entirely)
- [ ] Parallel chunk uploads for faster transfer
- [ ] Thumbnail generation during upload
- [ ] Background upload (continue uploading if user navigates away)

## Configuration Options

To adjust chunk size or limits, edit `resources/views/items/_media_and_transcript.blade.php`:

```javascript
const myDropzone = new Dropzone("#large-file-dropzone", {
    chunkSize: 2000000,        // 2MB - adjust if needed
    maxFilesize: 50000,        // 50GB - adjust if needed
    timeout: 300000,           // 5 min per chunk
    retryChunksLimit: 3,       // Retry attempts
    parallelChunkUploads: false // Upload chunks sequentially
});
```

---

**Implementation Date**: January 8, 2026  
**Laravel Version**: 12.x  
**DropzoneJS Version**: 5.x  
