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

    /**
     * Get the path to the WebVTT file for this transcript.
     * Generates the file if it doesn't exist (for non-VTT formats).
     * 
     * @return string|null The path to the VTT file, or null if not available
     */
    public function getVttPath(): ?string;
}