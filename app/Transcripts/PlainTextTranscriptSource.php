<?php

namespace App\Transcripts;

use App\Models\Media;
use Illuminate\Support\Facades\Storage;

/**
 * Plain text transcript source (no timestamps).
 */
class PlainTextTranscriptSource implements TranscriptSource
{
    protected array $segments = [];

    public function __construct(protected Media $media)
    {
        $this->loadSegments();
    }

    public function format(): string
    {
        return 'txt';
    }

    public function segments(): array
    {
        return $this->segments;
    }

    protected function loadSegments(): void
    {
        $content = Storage::disk('public')->get($this->media->path);
        if (!$content) {
            return;
        }

        // For plain text, create a single segment with no timestamps
        $this->segments = [
            [
                'start' => null,
                'end' => null,
                'text' => trim($content),
                'synopsis' => null,
                'keywords' => [],
            ]
        ];
    }
}