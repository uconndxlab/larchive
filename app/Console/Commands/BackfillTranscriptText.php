<?php

namespace App\Console\Commands;

use App\Models\Item;
use App\Models\Media;
use App\Services\MediaMetadataCache;
use Illuminate\Console\Command;

class BackfillTranscriptText extends Command
{
    protected $signature = 'transcripts:extract-text {--media= : Process only this Media ID} {--dry-run : Extract without saving}';

    protected $description = 'Cache transcript text without replacing existing or curated text';

    public function handle(MediaMetadataCache $cache): int
    {
        $counts = ['extracted' => 0, 'skipped' => 0, 'failed' => 0];
        $query = Media::where(fn ($query) => $query->where('is_transcript', true)
            ->orWhereIn('id', Item::withTrashed()->select('transcript_id')->whereNotNull('transcript_id')));
        if ($this->option('media')) {
            $query->whereKey($this->option('media'));
        }
        foreach ($query->lazyById() as $media) {
            $result = $cache->extract($media, true, (bool) $this->option('dry-run'));
            $counts[$result]++;
            if ($result === 'failed') {
                $this->warn("Failed to extract transcript for media {$media->id}; see application log.");
            }
        }
        $this->info(($this->option('dry-run') ? 'Dry run: ' : '')."extracted={$counts['extracted']}; skipped={$counts['skipped']}; failed={$counts['failed']}");

        return $counts['failed'] ? self::FAILURE : self::SUCCESS;
    }
}
