<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Maximum Upload Size (MB)
    |--------------------------------------------------------------------------
    |
    | Maximum file size for media uploads in megabytes.
    | This should match or be lower than your PHP upload_max_filesize.
    | 
    | Default: 200MB (suitable for most audio/video files)
    | For very large videos, consider increasing to 500MB or 1GB.
    |
    */
    'max_upload_size_mb' => env('MEDIA_MAX_UPLOAD_SIZE_MB', 200),

    /*
    |--------------------------------------------------------------------------
    | Allowed MIME Types
    |--------------------------------------------------------------------------
    |
    | File extensions allowed for upload.
    | These are validated by Laravel's 'mimes' rule.
    |
    */
    'allowed_mime_types' => [
        // Audio formats
        'mp3', 'wav', 'ogg', 'flac', 'm4a', 'aac', 'wma',
        
        // Video formats
        'mp4', 'mov', 'avi', 'wmv', 'flv', 'webm', 'mkv',
        
        // Image formats
        'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp', 'tiff',
        
        // Document formats
        'pdf', 'doc', 'docx', 'txt', 'rtf',
        
        // Transcript/subtitle formats
        'vtt', 'srt',
        
        // Archive formats
        'zip',
    ],

    /*
    |--------------------------------------------------------------------------
    | Storage Disk
    |--------------------------------------------------------------------------
    |
    | Default storage disk for media files.
    | 
    | Options:
    | - 'local' or 'public' for local storage (development only)
    | - 'archives' for S3-compatible object storage (production)
    |
    */
    'disk' => env('MEDIA_STORAGE_DISK', 'public'),

    /*
    |--------------------------------------------------------------------------
    | Processing Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for background processing of uploaded media.
    |
    */
    'processing' => [
        // Whether to process media asynchronously via queue
        'enabled' => env('MEDIA_PROCESSING_ENABLED', true),
        
        // Queue name for media processing jobs
        'queue' => env('MEDIA_PROCESSING_QUEUE', 'media'),
        
        // Extract metadata (duration, dimensions, etc.)
        'extract_metadata' => true,
        
        // Generate thumbnails for videos and images
        'generate_thumbnails' => env('MEDIA_GENERATE_THUMBNAILS', false),
        
        // Generate waveforms for audio files
        'generate_waveforms' => env('MEDIA_GENERATE_WAVEFORMS', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Thumbnail Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for thumbnail generation.
    |
    */
    'thumbnails' => [
        'disk' => env('MEDIA_THUMBNAIL_DISK', 'archives'),
        'path' => 'thumbnails',
        'width' => 400,
        'height' => 300,
        'quality' => 85,
    ],

    /*
    |--------------------------------------------------------------------------
    | Waveform Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for audio waveform generation.
    |
    */
    'waveforms' => [
        'disk' => env('MEDIA_WAVEFORM_DISK', 'archives'),
        'path' => 'waveforms',
        'width' => 1800,
        'height' => 280,
        'color' => '#3b82f6',
    ],
];
