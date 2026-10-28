<?php

namespace App\Http\Controllers;

use App\Models\Media;
use App\Services\TranscriptStorage;
use App\Transcripts\TranscriptSourceFactory;
use Illuminate\Support\Facades\Gate;

class TranscriptController extends Controller
{
    public function download(Media $media)
    {
        abort_unless(TranscriptStorage::isTranscript($media), 404);
        Gate::authorize('view', $media->item);
        $disk = TranscriptStorage::diskFor($media->path);
        abort_unless($disk->exists($media->path), 404);

        return $disk->download($media->path, $media->filename, [
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function captions(Media $media)
    {
        abort_unless(TranscriptStorage::isTranscript($media), 404);
        Gate::authorize('view', $media->item);
        $path = TranscriptSourceFactory::create($media)?->getVttPath();
        abort_unless($path, 404);
        $disk = TranscriptStorage::diskFor($path);
        abort_unless($disk->exists($path), 404);

        return response($disk->get($path), 200, [
            'Content-Type' => 'text/vtt; charset=UTF-8',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
