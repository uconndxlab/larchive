<?php

namespace App\Transcripts;

use App\Models\Media;
use App\Services\WebVttGenerator;
use Illuminate\Support\Facades\Storage;

/**
 * SRT transcript source.
 */
class SrtTranscriptSource implements TranscriptSource
{
    protected array $segments = [];
    protected ?string $vttPath = null;

    public function __construct(protected Media $media)
    {
        $this->loadSegments();
    }

    public function format(): string
    {
        return 'srt';
    }

    public function segments(): array
    {
        return $this->segments;
    }

    public function getVttPath(): ?string
    {
        // Check if we already generated it
        if ($this->vttPath !== null) {
            return $this->vttPath;
        }

        // Check if VTT file already exists
        if (WebVttGenerator::vttExists($this->media)) {
            $this->vttPath = WebVttGenerator::getVttPath($this->media);
            return $this->vttPath;
        }

        // Generate VTT file from parsed segments
        if (!empty($this->segments)) {
            $this->vttPath = WebVttGenerator::generateFromSegments($this->segments, $this->media);
        }

        return $this->vttPath;
    }

    protected function loadSegments(): void
    {
        $content = Storage::disk('public')->get($this->media->path);
        if (!$content) {
            return;
        }

        $this->segments = $this->parseSrt($content);
    }

    protected function parseSrt(string $content): array
    {
        $blocks = explode("\n\n", trim($content));
        $segments = [];

        foreach ($blocks as $block) {
            $lines = explode("\n", trim($block));
            if (count($lines) < 3) {
                continue;
            }

            // Skip sequence number (first line)
            $timestampLine = trim($lines[1]);

            // Parse timestamps: "00:00:01,000 --> 00:00:05,000"
            if (preg_match('/^(\d{2}:\d{2}:\d{2},\d{3})\s*-->\s*(\d{2}:\d{2}:\d{2},\d{3})/', $timestampLine, $matches)) {
                $start = $this->parseTimestamp($matches[1]);
                $end = $this->parseTimestamp($matches[2]);

                // Collect text (remaining lines)
                $text = implode(' ', array_slice($lines, 2));

                $segments[] = [
                    'start' => $start,
                    'end' => $end,
                    'text' => trim($text),
                    'synopsis' => null,
                    'keywords' => [],
                ];
            }
        }

        return $segments;
    }

    protected function parseTimestamp(string $timestamp): float
    {
        // SRT uses commas: 00:00:01,000
        $timestamp = str_replace(',', '.', $timestamp);
        return (new VttTranscriptSource($this->media))->parseTimestamp($timestamp);
    }
}