<?php

namespace App\Services;

use App\Models\Media;
use Illuminate\Support\Facades\Storage;

/**
 * Service for generating WebVTT files from transcript segments.
 */
class WebVttGenerator
{
    /**
     * Generate a WebVTT file from transcript segments.
     * 
     * @param array $segments Array of segments with start, end, and text
     * @param Media $sourceMedia The original transcript media
     * @return string|null The path to the generated WebVTT file, or null on failure
     */
    public static function generateFromSegments(array $segments, Media $sourceMedia): ?string
    {
        if (empty($segments)) {
            return null;
        }

        $vttContent = self::buildVttContent($segments);
        
        // Ensure UTF-8 encoding with BOM for compatibility
        $vttContent = "\xEF\xBB\xBF" . $vttContent;
        
        // Generate a filename based on the source media
        $baseName = pathinfo($sourceMedia->filename, PATHINFO_FILENAME);
        $vttFilename = $baseName . '.vtt';
        
        // Store in the same directory as the source
        $directory = dirname($sourceMedia->path);
        $vttPath = $directory . '/' . $vttFilename;
        
        // Save the VTT file with explicit UTF-8 encoding
        Storage::disk('public')->put($vttPath, $vttContent, ['mime_type' => 'text/vtt; charset=UTF-8']);
        
        return $vttPath;
    }

    /**
     * Build WebVTT content from segments.
     * 
     * @param array $segments Array of segments
     * @return string The WebVTT formatted content
     */
    protected static function buildVttContent(array $segments): string
    {
        $content = "WEBVTT\n\n";
        
        foreach ($segments as $index => $segment) {
            $start = $segment['start'] ?? 0;
            $end = $segment['end'] ?? ($start + 5); // Default 5 second duration if no end time
            $text = $segment['text'] ?? '';
            
            // Skip segments with no text
            if (empty($text)) {
                continue;
            }
            
            // Ensure text is UTF-8 encoded
            if (!mb_check_encoding($text, 'UTF-8')) {
                $text = mb_convert_encoding($text, 'UTF-8', 'auto');
            }
            
            // Add optional cue identifier
            $content .= ($index + 1) . "\n";
            
            // Add timestamp line
            $content .= self::formatTimestamp($start) . ' --> ' . self::formatTimestamp($end) . "\n";
            
            // Add text (wrap long lines at ~80 chars for readability)
            $content .= self::wrapText($text) . "\n\n";
        }
        
        return $content;
    }

    /**
     * Format a timestamp in WebVTT format (HH:MM:SS.mmm).
     * 
     * @param float|null $seconds Time in seconds
     * @return string Formatted timestamp
     */
    protected static function formatTimestamp(?float $seconds): string
    {
        if ($seconds === null) {
            $seconds = 0;
        }

        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $secs = $seconds % 60;
        
        return sprintf('%02d:%02d:%06.3f', $hours, $minutes, $secs);
    }

    /**
     * Wrap text to multiple lines if needed.
     * 
     * @param string $text The text to wrap
     * @param int $width Maximum line width
     * @return string Wrapped text
     */
    protected static function wrapText(string $text, int $width = 80): string
    {
        // Remove any existing line breaks
        $text = str_replace(["\r\n", "\r", "\n"], ' ', $text);
        
        // Wrap at specified width
        return wordwrap($text, $width, "\n", false);
    }

    /**
     * Generate a WebVTT file path for a given media file.
     * 
     * @param Media $media The media file
     * @return string The expected VTT file path
     */
    public static function getVttPath(Media $media): string
    {
        $directory = dirname($media->path);
        $baseName = pathinfo($media->filename, PATHINFO_FILENAME);
        return $directory . '/' . $baseName . '.vtt';
    }

    /**
     * Check if a WebVTT file exists for the given media.
     * 
     * @param Media $media The media file
     * @return bool True if VTT file exists
     */
    public static function vttExists(Media $media): bool
    {
        $vttPath = self::getVttPath($media);
        return Storage::disk('public')->exists($vttPath);
    }

    /**
     * Delete the WebVTT file for a given media (to force regeneration).
     * 
     * @param Media $media The media file
     * @return bool True if file was deleted
     */
    public static function deleteVtt(Media $media): bool
    {
        $vttPath = self::getVttPath($media);
        if (Storage::disk('public')->exists($vttPath)) {
            return Storage::disk('public')->delete($vttPath);
        }
        return false;
    }
}
