<?php

namespace App\Console\Commands;

use App\Models\Item;
use App\Models\Media;
use getID3;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class BackfillItemDurations extends Command
{
    protected $signature = 'items:backfill-durations
                            {--item= : Process only this item ID}
                            {--disk= : Override the configured media storage disk}
                            {--dry-run : Show durations without saving metadata}
                            {--overwrite : Replace existing oh.duration values}';

    protected $description = 'Set oh.duration in whole seconds from the longest readable audio/video recording per item';

    public function handle(): int
    {
        $itemId = $this->option('item');
        if ($itemId !== null && (! ctype_digit((string) $itemId) || (int) $itemId < 1)) {
            $this->error('--item must be a positive integer.');

            return self::FAILURE;
        }

        $diskName = $this->option('disk') ?: config('media.disk', 'public');
        $query = Item::query()->with(['media' => fn ($query) => $query->orderBy('id')]);
        if ($itemId !== null) {
            $query->whereKey($itemId);
        }

        $written = $skipped = $failed = $seen = 0;
        $this->info("Reading media from disk: {$diskName}");

        // Chunk instead of Item::get() so large archives do not exhaust memory.
        foreach ($query->lazyById(100) as $item) {
            $seen++;
            if (! $this->option('overwrite') && $item->metadata()->where('key', 'oh.duration')->exists()) {
                $skipped++;

                continue;
            }

            $recordings = $item->media->filter(function (Media $media) {
                $mime = strtolower((string) $media->mime_type);

                return ! $media->is_transcript
                    && (str_starts_with($mime, 'audio/') || str_starts_with($mime, 'video/'));
            });

            if ($recordings->isEmpty()) {
                $skipped++;

                continue;
            }

            $duration = null;
            $longestMediaId = null;
            foreach ($recordings as $media) {
                try {
                    $mediaDuration = $this->readDuration($media, $diskName);
                    if ($duration === null || $mediaDuration > $duration) {
                        $duration = $mediaDuration;
                        $longestMediaId = $media->id;
                    }
                } catch (Throwable $exception) {
                    $this->warn("Item {$item->id}, media {$media->id}: {$exception->getMessage()}");
                }
            }

            if ($duration === null) {
                $failed++;

                continue;
            }

            if (! $this->option('dry-run')) {
                // Re-running the command updates the same key, rather than adding duplicates.
                $item->metadata()->updateOrCreate(
                    ['key' => 'oh.duration'],
                    ['value' => (string) $duration],
                );
            }

            $written++;
            $action = $this->option('dry-run') ? 'Would set' : 'Set';
            $this->line("{$action} item {$item->id}: oh.duration={$duration} seconds (media {$longestMediaId})");
        }

        if ($itemId !== null && $seen === 0) {
            $this->error("Item {$itemId} was not found.");

            return self::FAILURE;
        }

        $action = $this->option('dry-run') ? 'Would write' : 'Wrote';
        $this->info("{$action}: {$written}; skipped (existing duration or no audio/video): {$skipped}; failed: {$failed}.");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    protected function readDuration(Media $media, string $diskName): int
    {
        $disk = Storage::disk($diskName);
        if (! $disk->exists($media->path)) {
            throw new RuntimeException("File missing on {$diskName}: {$media->path}");
        }

        $temporaryPath = null;
        $source = $destination = null;

        try {
            if (config("filesystems.disks.{$diskName}.driver") === 'local') {
                $localPath = $disk->path($media->path);
            } else {
                // getID3 needs a local file. Stream remote files to disk: recordings may be several GB.
                $temporaryPath = tempnam(sys_get_temp_dir(), 'item_duration_');
                if ($temporaryPath === false) {
                    throw new RuntimeException('Unable to create a temporary media file.');
                }

                $source = $disk->readStream($media->path);
                $destination = fopen($temporaryPath, 'wb');
                if (! is_resource($source) || ! is_resource($destination)) {
                    throw new RuntimeException('Unable to open media streams.');
                }

                $copied = stream_copy_to_stream($source, $destination);
                if ($copied === false || ! feof($source) || $copied !== $disk->size($media->path)) {
                    throw new RuntimeException('Unable to download the complete media file.');
                }

                fclose($destination);
                $destination = null;
                $localPath = $temporaryPath;
            }

            $info = (new getID3)->analyze($localPath);
            $seconds = $info['playtime_seconds'] ?? null;
            if (! empty($info['error'])) {
                throw new RuntimeException(implode('; ', $info['error']));
            }
            if (! is_numeric($seconds) || ! is_finite((float) $seconds) || (float) $seconds <= 0) {
                throw new RuntimeException('No valid duration was found in the media file.');
            }

            // oh.duration accepts integer seconds (or HH:MM:SS), not fractional seconds.
            return max(1, (int) round((float) $seconds));
        } finally {
            if (is_resource($source)) {
                fclose($source);
            }
            if (is_resource($destination)) {
                fclose($destination);
            }
            if (is_string($temporaryPath)) {
                unlink($temporaryPath);
            }
        }
    }
}
