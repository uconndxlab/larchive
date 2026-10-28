<?php

namespace App\Services;

use App\Models\Item;
use App\Models\Media;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;

class TranscriptStorage
{
    public static function isTranscript(Media $media): bool
    {
        return $media->is_transcript || Item::withTrashed()->where('transcript_id', $media->id)->exists();
    }

    // Read existing public files during rollout until the migration has removed them.
    public static function diskFor(string $path): Filesystem
    {
        $private = Storage::disk('transcripts');

        return $private->exists($path) ? $private : Storage::disk('public');
    }

    public static function delete(Media $media): void
    {
        $paths = array_unique([$media->path, WebVttGenerator::getVttPath($media)]);
        foreach (['transcripts', 'public'] as $disk) {
            Storage::disk($disk)->delete($paths);
        }
    }
}
