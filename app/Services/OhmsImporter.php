<?php

namespace App\Services;

use App\Models\Item;
use Illuminate\Support\Facades\Storage;
use SimpleXMLElement;

/**
 * OHMS XML Importer Service
 * 
 * Parses OHMS (Oral History Metadata Synchronizer) XML files and stores:
 * - Normalized JSON in items.ohms_json for fast viewer access
 * - Raw XML in storage/app/items/{item_id}/ohms.xml for archival
 */
class OhmsImporter
{
    /**
     * Import OHMS XML for an item.
     * 
     * @param Item $item The item to attach OHMS data to
     * @param string $xmlPath Absolute path to the OHMS XML file
     * @return void
     * @throws \Exception If XML is invalid
     */
    public function import(Item $item, string $xmlPath): void
    {
        if (!file_exists($xmlPath)) {
            throw new \Exception("OHMS XML file not found");
        }

        // Suppress XML warnings and parse
        libxml_use_internal_errors(true);
        $xml = simplexml_load_file($xmlPath);
        
        if ($xml === false) {
            $errors = libxml_get_errors();
            libxml_clear_errors();
            throw new \Exception("Failed to parse OHMS XML: " . ($errors[0]->message ?? 'Unknown error'));
        }

        // Normalize to JSON structure (resilient to missing fields)
        $normalized = $this->normalize($xml);

        // Store raw XML in storage
        $storagePath = "items/{$item->id}/ohms.xml";
        Storage::put($storagePath, file_get_contents($xmlPath));

        // Update item with normalized JSON
        $item->update(['ohms_json' => $normalized]);
    }

    /**
     * Normalize OHMS XML to our JSON schema.
     * Resilient to missing fields.
     * 
     * @param SimpleXMLElement $xml
     * @return array
     */
    protected function normalize(SimpleXMLElement $xml): array
    {
        $record = $xml->record ?? $xml;

        return [
            'title' => $this->getString($record->title),
            'duration_seconds' => $this->parseDuration($this->getString($record->duration)),
            'media_url' => $this->getString($record->media_url),
            'segments' => $this->parseSegments($record->index ?? $xml->index ?? null),
            'transcript' => $this->parseTranscript($record->transcript ?? $xml->transcript ?? null),
        ];
    }

    /**
     * Safely extract string from XML element.
     * 
     * @param mixed $element
     * @return string|null
     */
    protected function getString($element): ?string
    {
        if (!$element) {
            return null;
        }
        
        $str = trim((string)$element);
        return $str !== '' ? $str : null;
    }

    /**
     * Parse duration string to seconds.
     * Handles formats: "1:23:45", "23:45", "45", or empty.
     * 
     * @param string|null $duration
     * @return int|null Duration in seconds
     */
    protected function parseDuration(?string $duration): ?int
    {
        if (empty($duration)) {
            return null;
        }

        // Already in seconds
        if (is_numeric($duration)) {
            return (int)$duration;
        }

        // Parse HH:MM:SS, MM:SS, or SS format
        $parts = array_reverse(explode(':', $duration));
        $seconds = 0;
        
        if (isset($parts[0])) $seconds += (int)$parts[0]; // seconds
        if (isset($parts[1])) $seconds += (int)$parts[1] * 60; // minutes
        if (isset($parts[2])) $seconds += (int)$parts[2] * 3600; // hours

        return $seconds > 0 ? $seconds : null;
    }

    /**
     * Parse index points into normalized segments.
     * 
     * @param SimpleXMLElement|null $index
     * @return array
     */
    protected function parseSegments(?SimpleXMLElement $index): array
    {
        if (!$index || !isset($index->point)) {
            return [];
        }

        $segments = [];

        foreach ($index->point as $point) {
            $keywords = $this->parseList($this->getString($point->keywords), ',');
            $subjects = $this->parseList($this->getString($point->subjects), ';');
            
            $hyperlink = null;
            if ($this->getString($point->hyperlink)) {
                $hyperlink = [
                    'url' => $this->getString($point->hyperlink),
                    'text' => $this->getString($point->hyperlink_description) ?? $this->getString($point->hyperlink),
                ];
            }

            $segments[] = [
                'time' => $this->parseTimeToSeconds($this->getString($point->time)),
                'title' => $this->getString($point->title),
                'synopsis' => $this->getString($point->synopsis),
                'partial_transcript' => $this->getString($point->partial_transcript),
                'keywords' => $keywords,
                'subjects' => $subjects,
                'gps' => $this->getString($point->gps),
                'hyperlink' => $hyperlink,
            ];
        }

        return $segments;
    }

    /**
     * Parse delimited string into array.
     * 
     * @param string|null $str
     * @param string $delimiter
     * @return array
     */
    protected function parseList(?string $str, string $delimiter): array
    {
        if (empty($str)) {
            return [];
        }

        return array_map('trim', explode($delimiter, $str));
    }

    /**
     * Parse time string (HH:MM:SS) to seconds.
     * Also handles time already in seconds.
     * 
     * @param string|null $time
     * @return int
     */
    protected function parseTimeToSeconds(?string $time): int
    {
        if (empty($time)) {
            return 0;
        }

        // Already in seconds (numeric string)
        if (is_numeric($time)) {
            return (int)$time;
        }

        // Parse HH:MM:SS, MM:SS format
        if (str_contains($time, ':')) {
            $parts = array_reverse(explode(':', $time));
            $seconds = 0;
            
            if (isset($parts[0])) $seconds += (int)$parts[0]; // seconds
            if (isset($parts[1])) $seconds += (int)$parts[1] * 60; // minutes
            if (isset($parts[2])) $seconds += (int)$parts[2] * 3600; // hours

            return $seconds;
        }

        // Default: try to parse as integer
        return (int)$time;
    }

    /**
     * Parse transcript text.
     * 
     * @param SimpleXMLElement|null $transcript
     * @return string|null
     */
    protected function parseTranscript(?SimpleXMLElement $transcript): ?string
    {
        if (!$transcript) {
            return null;
        }

        $text = trim((string)$transcript);
        return !empty($text) ? $text : null;
    }
}
