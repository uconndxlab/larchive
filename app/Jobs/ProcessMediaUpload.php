<?php

namespace App\Jobs;

use App\Models\Media;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use getID3;

class ProcessMediaUpload implements ShouldQueue
{
    use Queueable;

    /**
     * The number of times the job may be attempted.
     */
    public $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     * Set to 10 minutes for large file processing.
     */
    public $timeout = 600;

    /**
     * The media model to process.
     */
    protected Media $media;

    /**
     * Create a new job instance.
     */
    public function __construct(Media $media)
    {
        $this->media = $media;
        
        // Use the media queue if configured
        $this->onQueue(config('media.processing.queue', 'media'));
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info("Processing media upload", [
            'media_id' => $this->media->id,
            'filename' => $this->media->filename,
        ]);

        try {
            // Mark as processing
            $this->media->markAsProcessing();

            // Extract metadata based on file type
            $metadata = $this->extractMetadata();
            
            // Save metadata to the database
            $this->media->update(['metadata' => $metadata]);

            // Dispatch additional processing jobs if enabled
            $this->dispatchAdditionalJobs($metadata);

            // Mark as ready
            $this->media->markAsReady();

            Log::info("Media processing completed", [
                'media_id' => $this->media->id,
                'metadata' => $metadata,
            ]);

        } catch (\Exception $e) {
            Log::error("Media processing failed", [
                'media_id' => $this->media->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Mark as failed with error message
            $this->media->markAsFailed($e->getMessage());

            // Re-throw to trigger job retry
            throw $e;
        }
    }

    /**
     * Extract metadata from the uploaded file.
     */
    protected function extractMetadata(): array
    {
        $metadata = [
            'mime_type' => $this->media->mime_type,
            'size_bytes' => $this->media->size,
            'size_human' => $this->formatBytes($this->media->size),
        ];

        // Get the file from storage
        $disk = Storage::disk(config('media.disk'));
        $path = $this->media->path;

        // Check if file exists
        if (!$disk->exists($path)) {
            throw new \Exception("File not found in storage: {$path}");
        }

        // For audio/video files, extract additional metadata using getID3
        if ($this->isAudioOrVideo()) {
            $metadata = array_merge($metadata, $this->extractAudioVideoMetadata($disk, $path));
        }

        // For images, extract dimensions
        if ($this->isImage()) {
            $metadata = array_merge($metadata, $this->extractImageMetadata($disk, $path));
        }

        return $metadata;
    }

    /**
     * Extract metadata from audio/video files using getID3.
     */
    protected function extractAudioVideoMetadata($disk, $path): array
    {
        $metadata = [];

        try {
            // Download file temporarily for analysis
            // (getID3 requires a local file path)
            $tempPath = tempnam(sys_get_temp_dir(), 'media_');
            file_put_contents($tempPath, $disk->get($path));

            // Use getID3 to analyze the file
            if (class_exists('getID3')) {
                $getID3 = new getID3();
                $info = $getID3->analyze($tempPath);

                if (isset($info['playtime_seconds'])) {
                    $metadata['duration'] = round($info['playtime_seconds'], 2);
                }

                if (isset($info['video']['resolution_x'])) {
                    $metadata['width'] = $info['video']['resolution_x'];
                }

                if (isset($info['video']['resolution_y'])) {
                    $metadata['height'] = $info['video']['resolution_y'];
                }

                if (isset($info['video']['frame_rate'])) {
                    $metadata['frame_rate'] = round($info['video']['frame_rate'], 2);
                }

                if (isset($info['audio']['bitrate'])) {
                    $metadata['audio_bitrate'] = $info['audio']['bitrate'];
                }

                if (isset($info['audio']['sample_rate'])) {
                    $metadata['sample_rate'] = $info['audio']['sample_rate'];
                }

                if (isset($info['audio']['channels'])) {
                    $metadata['channels'] = $info['audio']['channels'];
                }

                if (isset($info['fileformat'])) {
                    $metadata['format'] = $info['fileformat'];
                }

                if (isset($info['video']['codec'])) {
                    $metadata['video_codec'] = $info['video']['codec'];
                }

                if (isset($info['audio']['codec'])) {
                    $metadata['audio_codec'] = $info['audio']['codec'];
                }
            }

            // Clean up temp file
            @unlink($tempPath);

        } catch (\Exception $e) {
            Log::warning("Failed to extract audio/video metadata", [
                'media_id' => $this->media->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $metadata;
    }

    /**
     * Extract metadata from image files.
     */
    protected function extractImageMetadata($disk, $path): array
    {
        $metadata = [];

        try {
            // Download file temporarily
            $tempPath = tempnam(sys_get_temp_dir(), 'image_');
            file_put_contents($tempPath, $disk->get($path));

            $imageSize = getimagesize($tempPath);
            
            if ($imageSize) {
                $metadata['width'] = $imageSize[0];
                $metadata['height'] = $imageSize[1];
                $metadata['format'] = image_type_to_extension($imageSize[2], false);
            }

            // Clean up
            @unlink($tempPath);

        } catch (\Exception $e) {
            Log::warning("Failed to extract image metadata", [
                'media_id' => $this->media->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $metadata;
    }

    /**
     * Dispatch additional processing jobs based on config.
     */
    protected function dispatchAdditionalJobs(array $metadata): void
    {
        // Example: Generate thumbnails for videos/images
        // if (config('media.processing.generate_thumbnails') && $this->isImage()) {
        //     GenerateThumbnail::dispatch($this->media);
        // }

        // Example: Generate waveform for audio
        // if (config('media.processing.generate_waveforms') && $this->isAudio()) {
        //     GenerateWaveform::dispatch($this->media);
        // }

        // These jobs would be created separately as needed
    }

    /**
     * Check if the file is audio or video.
     */
    protected function isAudioOrVideo(): bool
    {
        $mimeType = $this->media->mime_type;
        return str_starts_with($mimeType, 'audio/') || str_starts_with($mimeType, 'video/');
    }

    /**
     * Check if the file is an image.
     */
    protected function isImage(): bool
    {
        return str_starts_with($this->media->mime_type, 'image/');
    }

    /**
     * Check if the file is audio.
     */
    protected function isAudio(): bool
    {
        return str_starts_with($this->media->mime_type, 'audio/');
    }

    /**
     * Format bytes to human-readable size.
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Media processing job failed permanently", [
            'media_id' => $this->media->id,
            'error' => $exception->getMessage(),
        ]);

        $this->media->markAsFailed(
            "Processing failed after {$this->tries} attempts: " . $exception->getMessage()
        );
    }
}
