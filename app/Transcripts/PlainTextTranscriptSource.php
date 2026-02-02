<?php

namespace App\Transcripts;

use App\Models\Media;
use App\Services\WebVttGenerator;
use Illuminate\Support\Facades\Storage;

/**
 * Plain text transcript source (with optional timestamps).
 */
class PlainTextTranscriptSource implements TranscriptSource
{
    protected array $segments = [];
    protected ?string $vttPath = null;

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
            // If segments already have proper timestamps, use them directly
            // Otherwise add timing for plain text
            $segmentsWithTime = $this->ensureTimestamps($this->segments);
            
            $this->vttPath = WebVttGenerator::generateFromSegments($segmentsWithTime, $this->media);
        }

        return $this->vttPath;
    }

    protected function loadSegments(): void
    {
        $content = Storage::disk('public')->get($this->media->path);
        if (!$content) {
            return;
        }

        // Try to parse timestamps from the text
        $parsed = $this->parseTimestampedText($content);
        
        if (!empty($parsed)) {
            $this->segments = $parsed;
        } else {
            // Fall back to single segment with no timestamps
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

    /**
     * Parse plain text that contains embedded timestamps.
     * Supports formats like:
     * - "Speaker Name (MM:SS):"
     * - "Speaker Name (HH:MM:SS):"
     * 
     * @param string $content The plain text content
     * @return array Array of segments with timestamps
     */
    protected function parseTimestampedText(string $content): array
    {
        $segments = [];
        $lines = explode("\n", $content);
        $currentSegment = null;
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            // Skip empty lines
            if (empty($line)) {
                continue;
            }
            
            // Check for timestamp pattern: "Name (MM:SS):" or "Name (HH:MM:SS):"
            if (preg_match('/^(.+?)\s*\((\d{1,2}):(\d{2})(?::(\d{2}))?\):(.*)$/u', $line, $matches)) {
                // Save previous segment if exists
                if ($currentSegment !== null) {
                    $segments[] = $currentSegment;
                }
                
                // Parse timestamp
                $speaker = trim($matches[1]);
                // Check if we have HH:MM:SS (3 parts) or just MM:SS (2 parts)
                $hasHours = !empty($matches[4]);
                
                if ($hasHours) {
                    // HH:MM:SS format
                    $hours = (int)$matches[2];
                    $minutes = (int)$matches[3];
                    $seconds = (int)$matches[4];
                } else {
                    // MM:SS format
                    $hours = 0;
                    $minutes = (int)$matches[2];
                    $seconds = (int)$matches[3];
                }
                
                $remainingText = trim($matches[5]);
                
                $timestamp = $hours * 3600 + $minutes * 60 + $seconds;
                
                // Start new segment
                $currentSegment = [
                    'start' => (float)$timestamp,
                    'end' => null, // Will be filled later
                    'text' => $speaker . ': ' . $remainingText,
                    'synopsis' => null,
                    'keywords' => [],
                ];
            } else {
                // Continuation of current segment
                if ($currentSegment !== null) {
                    $currentSegment['text'] .= ' ' . $line;
                }
            }
        }
        
        // Add the last segment
        if ($currentSegment !== null) {
            $segments[] = $currentSegment;
        }
        
        // Calculate end times
        if (!empty($segments)) {
            $segments = $this->calculateEndTimes($segments);
        }
        
        return $segments;
    }

    /**
     * Calculate end times for segments based on next segment's start time.
     * 
     * @param array $segments Segments with start times
     * @return array Segments with end times
     */
    protected function calculateEndTimes(array $segments): array
    {
        $count = count($segments);
        
        for ($i = 0; $i < $count; $i++) {
            if ($i < $count - 1) {
                // Use next segment's start time as end time
                $segments[$i]['end'] = $segments[$i + 1]['start'];
            } else {
                // For last segment, estimate duration based on text length
                $wordCount = str_word_count($segments[$i]['text'] ?? '');
                $estimatedDuration = max(5, $wordCount / 2.5); // ~2.5 words per second, min 5 seconds
                $segments[$i]['end'] = $segments[$i]['start'] + $estimatedDuration;
            }
        }
        
        return $segments;
    }

    /**
     * Ensure segments have valid timestamps for VTT generation.
     * 
     * @param array $segments Input segments
     * @return array Segments with guaranteed timestamps
     */
    protected function ensureTimestamps(array $segments): array
    {
        // If first segment has no start time, add one
        if (isset($segments[0]) && $segments[0]['start'] === null) {
            $segments[0]['start'] = 0;
            // Estimate duration based on text length
            $wordCount = str_word_count($segments[0]['text'] ?? '');
            $estimatedDuration = max(10, $wordCount / 2.5); // Minimum 10 seconds
            $segments[0]['end'] = $estimatedDuration;
        }
        
        return $segments;
    }
}