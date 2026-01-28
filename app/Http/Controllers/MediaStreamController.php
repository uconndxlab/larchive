<?php

namespace App\Http\Controllers;

use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MediaStreamController extends Controller
{
    /**
     * Stream a media file with HTTP range request support for seekable audio/video.
     */
    public function stream(Request $request, Media $media)
    {
        // Check if file exists
        $path = $media->path;
        $disk = Storage::disk('public');
        
        if (!$disk->exists($path)) {
            abort(404, 'Media file not found');
        }

        $fullPath = $disk->path($path);
        $fileSize = $disk->size($path);
        $mimeType = $media->mime_type;

        // Get range header
        $range = $request->header('Range');

        if (!$range) {
            // No range requested, send entire file
            return response()->file($fullPath, [
                'Content-Type' => $mimeType,
                'Accept-Ranges' => 'bytes',
            ]);
        }

        // Parse range header (e.g., "bytes=0-1024")
        preg_match('/bytes=(\d+)-(\d+)?/', $range, $matches);
        $start = (int) $matches[1];
        $end = isset($matches[2]) && $matches[2] !== '' ? (int) $matches[2] : $fileSize - 1;

        // Validate range
        if ($start > $end || $start >= $fileSize) {
            return response('Invalid range', 416)->header('Content-Range', "bytes */{$fileSize}");
        }

        // Calculate length
        $length = $end - $start + 1;

        // Stream the partial content
        return response()->stream(function () use ($fullPath, $start, $length) {
            $stream = fopen($fullPath, 'rb');
            fseek($stream, $start);
            $buffer = 8192; // 8KB chunks
            $remaining = $length;

            while (!feof($stream) && $remaining > 0) {
                $chunkSize = min($buffer, $remaining);
                echo fread($stream, $chunkSize);
                $remaining -= $chunkSize;
                flush();
            }

            fclose($stream);
        }, 206, [
            'Content-Type' => $mimeType,
            'Content-Length' => $length,
            'Content-Range' => "bytes {$start}-{$end}/{$fileSize}",
            'Accept-Ranges' => 'bytes',
            'Cache-Control' => 'public, max-age=31536000',
        ]);
    }
}
