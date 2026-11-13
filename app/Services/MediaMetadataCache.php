<?php

namespace App\Services;

use App\Models\Media;
use Illuminate\Support\Facades\Log;
use Throwable;

class MediaMetadataCache
{
    public function __construct(private MediaTextExtractor $extractor) {}

    public function extract(Media $media, bool $transcript, bool $dryRun = false, ?string $disk = null): string
    {
        if ($transcript ? $media->transcript_text !== null : $media->duration_seconds !== null) {
            return 'skipped';
        }
        try {
            $values = $transcript
                ? $this->extractor->transcript($media)
                : $this->extractor->duration($media, $disk);
            if (! $dryRun) {
                // Conditional write protects curated text and replacement files from
                // a concurrent worker finishing extraction of an older source.
                $query = Media::whereKey($media->id)->where('path', $media->path)
                    ->where('filename', $media->filename)->where('mime_type', $media->mime_type)
                    ->where('format', $media->format)->where('is_transcript', $media->is_transcript);
                if ($transcript) {
                    $query->whereNull('transcript_text')->update([
                        'transcript_text' => $values['text'],
                        'transcript_segments' => $values['segments'],
                        'transcript_extracted_at' => now(),
                    ]);
                } else {
                    $query->whereNull('duration_seconds')->update(['duration_seconds' => $values]);
                }
            }

            return 'extracted';
        } catch (Throwable $exception) {
            // Keep document contents, process output and storage paths out of logs/API.
            Log::warning('Media metadata extraction failed', [
                'media_id' => $media->id,
                'kind' => $transcript ? 'transcript' : 'duration',
                'exception' => $exception::class,
            ]);

            return 'failed';
        }
    }
}
