<?php

namespace App\Transcripts;

use App\Models\Media;
use Illuminate\Support\Facades\Storage;

/**
 * WebVTT transcript source.
 */
class VttTranscriptSource implements TranscriptSource
{
    protected array $segments = [];

    public function __construct(protected Media $media)
    {
        $this->loadSegments();
    }

    public function format(): string
    {
        return 'vtt';
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

        $this->segments = $this->parseVtt($content);
    }

    protected function parseVtt(string $content): array
    {
        $lines = explode("\n", $content);
        $segments = [];
        $i = 0;

        // Skip WEBVTT header and blank lines
        while ($i < count($lines)) {
            $line = trim($lines[$i]);
            // Look for first timestamp
            if (preg_match('/^\d{2}:\d{2}/', $line)) {
                break;
            }
            $i++;
        }

        while ($i < count($lines)) {
            $line = trim($lines[$i]);

            // Look for timestamp line (e.g., "00:00:01.000 --> 00:00:05.000")
            if (preg_match('/^(\d{2}:\d{2}(?::\d{2})?\.\d{3})\s*-->\s*(\d{2}:\d{2}(?::\d{2})?\.\d{3})/', $line, $matches)) {
                $start = $this->parseTimestamp($matches[1]);
                $end = $this->parseTimestamp($matches[2]);

                // Collect text lines until next cue or end
                $text = '';
                $i++;
                while ($i < count($lines)) {
                    $currentLine = trim($lines[$i]);
                    // Stop at empty line or next timestamp
                    if ($currentLine === '' || preg_match('/^\d{2}:\d{2}/', $currentLine)) {
                        break;
                    }
                    $text .= $currentLine . ' ';
                    $i++;
                }

                $segments[] = [
                    'start' => $start,
                    'end' => $end,
                    'text' => trim($text),
                    'synopsis' => null,
                    'keywords' => [],
                ];
            }
            $i++;
        }

        return $segments;
    }

    protected function parseTimestamp(string $timestamp): float
    {
        // Handle HH:MM:SS.mmm or MM:SS.mmm
        $parts = explode(':', $timestamp);
        $seconds = 0.0;

        if (count($parts) === 3) {
            // HH:MM:SS.mmm
            $seconds += (float)$parts[0] * 3600; // hours
            $seconds += (float)$parts[1] * 60;   // minutes
            $seconds += (float)$parts[2];        // seconds.mmm
        } elseif (count($parts) === 2) {
            // MM:SS.mmm
            $seconds += (float)$parts[0] * 60;   // minutes
            $seconds += (float)$parts[1];        // seconds.mmm
        }

        return $seconds;
    }
}