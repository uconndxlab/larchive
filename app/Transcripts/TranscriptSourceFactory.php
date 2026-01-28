<?php

namespace App\Transcripts;

use App\Models\Item;
use App\Models\Media;

/**
 * Factory for creating TranscriptSource instances.
 */
class TranscriptSourceFactory
{
    /**
     * Create a TranscriptSource for the given Item or Media.
     * Returns null if no transcript exists.
     */
    public static function create(Item|Media $source): ?TranscriptSource
    {
        if ($source instanceof Item) {
            // First try new transcripts (is_transcript=true)
            $transcript = $source->media()->transcripts()->first();
            
            // If no new transcripts, check legacy transcript_id
            if (!$transcript && $source->transcript_id) {
                $transcript = $source->transcript;
            }
            
            if (!$transcript) {
                return null;
            }
            $source = $transcript;
        }

        // $source is now Media
        // Assume it's a transcript if passed to this factory

        // Detect format if not set (for legacy transcripts)
        $format = $source->format ?: $this->detectFormat($source);

        return match ($format) {
            'ohms' => new OhmsTranscriptSource($source),
            'vtt' => new VttTranscriptSource($source),
            'srt' => new SrtTranscriptSource($source),
            'txt' => new PlainTextTranscriptSource($source),
            default => null,
        };
    }

    protected static function detectFormat(Media $media): string
    {
        $extension = strtolower(pathinfo($media->filename, PATHINFO_EXTENSION));
        $mimeType = $media->mime_type;

        return match ($extension) {
            'xml' => 'ohms',
            'vtt' => 'vtt',
            'srt' => 'srt',
            'txt' => 'txt',
            default => match ($mimeType) {
                'application/xml', 'text/xml' => 'ohms',
                'text/vtt' => 'vtt',
                'text/plain' => 'txt',
                default => 'txt',
            },
        };
    }
}