<?php

namespace App\Console\Commands;

use App\Models\Item;
use App\Models\Media;
use App\Services\WebVttGenerator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class PrivatizeTranscripts extends Command
{
    protected $signature = 'transcripts:privatize
        {--dry-run : Report files without changing storage}
        {--remove-public : Remove public copies after SHA-256 verification}';

    protected $description = 'Copy existing transcripts and captions to private storage and optionally remove public copies';

    public function handle(): int
    {
        $errors = 0;
        $seen = [];
        $transcripts = Media::query()->where(function ($query) {
            $query->where('is_transcript', true)
                ->orWhereIn('id', Item::withTrashed()->select('transcript_id')->whereNotNull('transcript_id'));
        });

        foreach ($transcripts->lazyById() as $media) {
            foreach (array_unique([$media->path, WebVttGenerator::getVttPath($media)]) as $path) {
                if (isset($seen[$path])) {
                    continue;
                }
                $seen[$path] = true;
                try {
                    $this->migrateFile($path, $path === $media->path);
                } catch (Throwable $e) {
                    $this->error($path.': '.$e->getMessage());
                    $errors++;
                }
            }
        }

        $this->info('Checked '.count($seen).' file paths; '.$errors.' errors.');

        return $errors ? self::FAILURE : self::SUCCESS;
    }

    private function migrateFile(string $path, bool $required): void
    {
        $public = Storage::disk('public');
        $private = Storage::disk('transcripts');
        if (! $public->exists($path)) {
            if ($required && ! $private->exists($path)) {
                throw new RuntimeException('Transcript is missing from both disks.');
            }

            return;
        }

        $this->line(($this->option('dry-run') ? 'Would migrate: ' : 'Verifying: ').$path);
        if ($this->option('dry-run')) {
            return;
        }

        if (! $private->exists($path)) {
            $stream = $public->readStream($path);
            if (! is_resource($stream)) {
                throw new RuntimeException('Cannot read public copy.');
            }
            try {
                if (! $private->put($path, $stream)) {
                    throw new RuntimeException('Private copy failed.');
                }
            } finally {
                fclose($stream);
            }
        }

        if ($this->checksum('public', $path) !== $this->checksum('transcripts', $path)) {
            throw new RuntimeException('Checksums differ; public copy retained.');
        }

        if ($this->option('remove-public')) {
            // Do not break an unrelated media record that shares this storage path.
            $shared = Media::where('path', $path)->where('is_transcript', false)
                ->whereNotIn('id', Item::withTrashed()->select('transcript_id')->whereNotNull('transcript_id'))
                ->exists();
            if ($shared) {
                throw new RuntimeException('Path is shared with non-transcript media; public copy retained.');
            }
            if (! $public->delete($path)) {
                throw new RuntimeException('Could not remove public copy.');
            }
            $this->line('Removed public copy: '.$path);
        }
    }

    private function checksum(string $disk, string $path): string
    {
        $stream = Storage::disk($disk)->readStream($path);
        if (! is_resource($stream)) {
            throw new RuntimeException('Cannot read file for verification.');
        }
        try {
            $hash = hash_init('sha256');
            if (hash_update_stream($hash, $stream) === false) {
                throw new RuntimeException('Checksum failed.');
            }

            return hash_final($hash);
        } finally {
            fclose($stream);
        }
    }
}
