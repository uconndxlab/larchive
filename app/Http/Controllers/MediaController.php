<?php

namespace App\Http\Controllers;

use App\Http\Requests\UploadMediaRequest;
use App\Jobs\ProcessMediaUpload;
use App\Models\Item;
use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class MediaController extends Controller
{
    /**
     * Display a listing of the resource (HTMX partial).
     */
    public function index(Item $item)
    {
        $item->load('media');
        return view('media._list', compact('item'));
    }

    /**
     * Store multiple uploaded files using streamed uploads and queued processing.
     */
    public function store(UploadMediaRequest $request, Item $item)
    {
        // Check if files were actually uploaded
        if (!$request->hasFile('files')) {
            if ($request->header('HX-Request')) {
                // Return the media list with an error message for HTMX
                $item->load('media');
                return view('media._list', compact('item'))
                    ->with('uploadError', 'Please select at least one file to upload.');
            }
            
            return redirect()->back()->withErrors(['files' => 'Please select at least one file to upload.']);
        }
        
        $files = $request->file('files');
        $altText = $request->input('alt_text');
        
        // Determine storage disk from config
        $disk = config('media.disk', 'public');

        // Get current max sort_order
        $maxSort = $item->media()->max('sort_order') ?? -1;

        $uploadedMedia = [];

        foreach ($files as $index => $file) {
            try {
                $originalName = $file->getClientOriginalName();
                $mimeType = $file->getMimeType();
                $size = $file->getSize();

                // Stream the file to storage (efficient for large files)
                // This uses a stream internally, avoiding loading entire file into memory
                $path = Storage::disk($disk)->putFile(
                    "items/{$item->id}",
                    $file,
                    'private' // Use private visibility for S3
                );

                // Create media record with 'uploading' status
                $media = $item->media()->create([
                    'filename' => $originalName,
                    'path' => $path,
                    'mime_type' => $mimeType,
                    'size' => $size,
                    'alt_text' => $altText,
                    'sort_order' => ++$maxSort,
                    'processing_status' => 'uploaded',
                ]);

                $uploadedMedia[] = $media;

                // Dispatch queued job for metadata extraction if enabled
                if (config('media.processing.enabled', true)) {
                    ProcessMediaUpload::dispatch($media);
                    
                    Log::info("Dispatched media processing job", [
                        'media_id' => $media->id,
                        'filename' => $originalName,
                        'size' => $size,
                    ]);
                } else {
                    // If processing is disabled, mark as ready immediately
                    $media->markAsReady();
                }

            } catch (\Exception $e) {
                Log::error("Failed to upload media file", [
                    'item_id' => $item->id,
                    'filename' => $originalName ?? 'unknown',
                    'error' => $e->getMessage(),
                ]);

                // Continue with remaining files
                continue;
            }
        }

        $item->load('media');
        return view('media._list', compact('item'));
    }

    /**
     * Update the specified media (e.g., alt text).
     */
    public function update(Request $request, Media $media)
    {
        $request->validate([
            'alt_text' => 'nullable|string|max:255',
        ]);

        $media->update([
            'alt_text' => $request->input('alt_text'),
        ]);

        $item = $media->item;
        $item->load('media');

        // Return just the updated row
        return view('media._row', compact('media', 'item'));
    }

    /**
     * Remove the specified media file.
     */
    public function destroy(Media $media)
    {
        $item = $media->item;

        // Delete file from storage
        if (Storage::disk('public')->exists($media->path)) {
            Storage::disk('public')->delete($media->path);
        }

        $media->delete();

        $item->load('media');
        return view('media._list', compact('item'));
    }

    /**
     * Reorder media files.
     */
    public function reorder(Request $request, Item $item)
    {
        $request->validate([
            'order' => 'required|string',
        ]);

        $mediaIds = json_decode($request->input('order'), true);

        foreach ($mediaIds as $index => $mediaId) {
            Media::where('id', $mediaId)
                ->where('item_id', $item->id)
                ->update(['sort_order' => $index]);
        }

        $item->load('media');
        return view('media._list', compact('item'));
    }
}
