<?php

namespace App\Transcripts;

use App\Models\Item;
use App\Models\Media;
use App\Services\WebVttGenerator;
use Illuminate\Support\Facades\Storage;
use SimpleXMLElement;

/**
 * OHMS XML transcript source.
 */
class OhmsTranscriptSource implements TranscriptSource
{
    protected array $segments = [];
    protected ?string $vttPath = null;

    public function __construct(protected Item|Media $source)
    {
        $this->loadSegments();
    }

    public function format(): string
    {
        return 'ohms';
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

        // Get the Media object for VTT generation
        $media = $this->source instanceof Media ? $this->source : $this->source->media()->transcripts()->first();
        
        if (!$media) {
            return null;
        }

        // Check if VTT file already exists
        if (WebVttGenerator::vttExists($media)) {
            $this->vttPath = WebVttGenerator::getVttPath($media);
            return $this->vttPath;
        }

        // Generate VTT file from parsed segments
        if (!empty($this->segments)) {
            // Calculate end times for OHMS segments (which don't have them)
            $segmentsWithEnds = $this->addEndTimes($this->segments);
            $this->vttPath = WebVttGenerator::generateFromSegments($segmentsWithEnds, $media);
        }

        return $this->vttPath;
    }

    /**
     * Add estimated end times to OHMS segments which only have start times.
     */
    protected function addEndTimes(array $segments): array
    {
        $result = [];
        $count = count($segments);
        
        for ($i = 0; $i < $count; $i++) {
            $segment = $segments[$i];
            
            // If there's a next segment, use its start time as this segment's end time
            if ($i < $count - 1) {
                $segment['end'] = $segments[$i + 1]['start'];
            } else {
                // For the last segment, estimate duration based on text length
                // Rough estimate: 150 words per minute = 2.5 words per second
                $wordCount = str_word_count($segment['text'] ?? '');
                $estimatedDuration = max(5, $wordCount / 2.5); // Minimum 5 seconds
                $segment['end'] = $segment['start'] + $estimatedDuration;
            }
            
            $result[] = $segment;
        }
        
        return $result;
    }

    protected function loadSegments(): void
    {
        $xmlContent = $this->getXmlContent();
        if (!$xmlContent) {
            return;
        }

        // Suppress XML warnings and parse
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($xmlContent);

        if ($xml === false) {
            return;
        }

        $this->segments = $this->parseSegments($xml);
    }

    protected function getXmlContent(): ?string
    {
        if ($this->source instanceof Media) {
            return Storage::disk('public')->get($this->source->path);
        }

        // For Item, check if ohms_json exists (legacy), or find transcript media
        if (!empty($this->source->ohms_json['segments'])) {
            // Legacy: segments already parsed in ohms_json
            $this->segments = $this->convertLegacySegments($this->source->ohms_json['segments']);
            return null;
        }

        $transcript = $this->source->media()->transcripts()->first();
        if ($transcript) {
            return Storage::get($transcript->path);
        }

        return null;
    }

    protected function parseSegments(SimpleXMLElement $xml): array
    {
        $record = $xml->record ?? $xml;
        $index = $record->index ?? $xml->index ?? null;

        if (!$index || !isset($index->point)) {
            return [];
        }

        $segments = [];

        foreach ($index->point as $point) {
            $start = $this->parseTimeToSeconds($this->getString($point->time));
            $title = $this->getString($point->title);
            $synopsis = $this->getString($point->synopsis);
            $partialTranscript = $this->getString($point->partial_transcript);
            $keywords = $this->parseList($this->getString($point->keywords), ',');

            $segments[] = [
                'start' => $start,
                'end' => null, // OHMS doesn't have end times
                'text' => $partialTranscript ?: $title ?: '',
                'synopsis' => $synopsis,
                'keywords' => $keywords,
            ];
        }

        return $segments;
    }

    protected function convertLegacySegments(array $legacySegments): array
    {
        $segments = [];
        foreach ($legacySegments as $seg) {
            $segments[] = [
                'start' => $seg['time'] ?? 0,
                'end' => null,
                'text' => $seg['partial_transcript'] ?? $seg['title'] ?? '',
                'synopsis' => $seg['synopsis'] ?? null,
                'keywords' => $seg['keywords'] ?? [],
            ];
        }
        return $segments;
    }

    protected function getString($element): ?string
    {
        if (!$element) {
            return null;
        }

        $str = trim((string)$element);
        return $str !== '' ? $str : null;
    }

    protected function parseList(?string $str, string $delimiter): array
    {
        if (empty($str)) {
            return [];
        }

        return array_map('trim', explode($delimiter, $str));
    }

    protected function parseTimeToSeconds(?string $time): float
    {
        if (empty($time)) {
            return 0.0;
        }

        // Already in seconds (numeric string)
        if (is_numeric($time)) {
            return (float)$time;
        }

        // Parse HH:MM:SS, MM:SS format
        if (str_contains($time, ':')) {
            $parts = array_reverse(explode(':', $time));
            $seconds = 0.0;

            if (isset($parts[0])) $seconds += (float)$parts[0]; // seconds
            if (isset($parts[1])) $seconds += (float)$parts[1] * 60; // minutes
            if (isset($parts[2])) $seconds += (float)$parts[2] * 3600; // hours

            return $seconds;
        }

        // Default: try to parse as float
        return (float)$time;
    }
}