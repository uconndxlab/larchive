<?php

namespace App\Http\Controllers;

use App\Models\Media;
use App\Services\TranscriptStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MediaStreamController extends Controller
{
    /**
     * Stream a media file with HTTP range request support for seekable audio/video.
     */
    public function stream(Request $request, Media $media)
    {
        Gate::authorize('view', $media->item);

        // Check if file exists
        $path = $media->path;
        
        // Strip 'public/' prefix if present (paths stored as 'public/items/...')
        $path = preg_replace('#^public/#', '', $path);
        
        $isTranscript = TranscriptStorage::isTranscript($media);
        if ($isTranscript) {
            abort_unless($request->user(), 401);
        }
        $disk = $isTranscript ? TranscriptStorage::diskFor($path) : Storage::disk('public');
        
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
                'Cache-Control' => 'private, no-store',
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
            'Cache-Control' => 'private, no-store',
        ]);
    }

    /**
     * Serve a VTT file with proper UTF-8 headers.
     */
    public function serveVtt(string $path)
    {
        // Sanitize the path
        $path = ltrim($path, '/');

        $media = Media::query()
            ->where(function ($query) use ($path) {
                $query->where('path', $path)
                    ->orWhere('path', 'public/' . $path);
            })
            ->first();

        if ($media) {
            Gate::authorize('view', $media->item);
        } else {
            abort(404, 'VTT file not found');
        }
        
        $disk = TranscriptStorage::diskFor($path);
        
        if (!$disk->exists($path)) {
            abort(404, 'VTT file not found');
        }

        $content = $disk->get($path);
        
        return response($content)
            ->header('Content-Type', 'text/vtt; charset=UTF-8')
            ->header('Content-Disposition', 'inline; filename="' . basename($path) . '"')
            ->header('Cache-Control', 'private, no-store');
    }
}
