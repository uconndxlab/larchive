<?php

namespace App\Console\Commands;

use App\Models\Item;
use App\Models\Media;
use App\Services\MediaMetadataCache;
use Illuminate\Console\Command;

class BackfillMediaDurations extends Command
{
    protected $signature = 'media:backfill-durations {--media= : Process only this Media ID} {--disk= : Override the recording storage disk} {--dry-run : Probe without saving}';

    protected $description = 'Cache fractional recording durations using FFprobe';

    public function handle(MediaMetadataCache $cache): int
    {
        $counts = ['extracted' => 0, 'skipped' => 0, 'failed' => 0];
        $query = Media::where('is_transcript', false)
            ->whereNotIn('id', Item::withTrashed()->select('transcript_id')->whereNotNull('transcript_id'))
            ->where(fn ($query) => $query->where('mime_type', 'like', 'audio/%')->orWhere('mime_type', 'like', 'video/%'));
        if ($this->option('media')) {
            $query->whereKey($this->option('media'));
        }
        foreach ($query->lazyById() as $media) {
            $result = $cache->extract($media, false, (bool) $this->option('dry-run'), $this->option('disk'));
            $counts[$result]++;
            if ($result === 'failed') {
                $this->warn("Failed to probe recording for media {$media->id}; see application log.");
            }
        }
        $this->info(($this->option('dry-run') ? 'Dry run: ' : '')."extracted={$counts['extracted']}; skipped={$counts['skipped']}; failed={$counts['failed']}");

        return $counts['failed'] ? self::FAILURE : self::SUCCESS;
    }
}
