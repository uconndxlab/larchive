<?php

namespace App\Transcripts;

/**
 * Interface for transcript sources that normalize different formats into segments.
 */
interface TranscriptSource
{
    /**
     * Get the format identifier (e.g., 'ohms', 'vtt', 'srt', 'txt').
     */
    public function format(): string;

    /**
     * Get normalized segments array.
     * Each segment: ['start' => float, 'end' => float|null, 'text' => string, 'synopsis' => string|null, 'keywords' => array]
     */
    public function segments(): array;
}