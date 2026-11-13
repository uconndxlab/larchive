<?php

namespace App\Jobs;

use App\Models\Media;
use App\Services\MediaMetadataCache;
use App\Services\TranscriptStorage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ExtractMediaMetadata implements ShouldQueue
{
    use Queueable;

    public $timeout = 300;

    public function __construct(public int $mediaId)
    {
        $this->onQueue(config('media.processing.queue', 'media'));
        $this->afterCommit();
    }

    public function handle(MediaMetadataCache $cache): void
    {
        $media = Media::find($this->mediaId);
        if (! $media) {
            return;
        }
        if (TranscriptStorage::isTranscript($media)) {
            $cache->extract($media, true);
        } elseif (str_starts_with($media->mime_type, 'audio/') || str_starts_with($media->mime_type, 'video/')) {
            $cache->extract($media, false);
        }
    }
}
