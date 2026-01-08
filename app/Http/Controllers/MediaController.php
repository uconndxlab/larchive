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
        return view('items._media_list', compact('item'));
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
                return view('items._media_list', compact('item'))
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
        return view('items._media_list', compact('item'));
    }

    /**
     * Update the specified media (e.g., alt text).
     */
    public function update(Request $request, Media $media)
    {
        $request->validate([
            'alt_text' => 'nullable|string|max:255',
            'is_featured' => 'nullable|boolean',
            'media_type' => 'nullable|in:main,supplemental',
            'label' => 'nullable|string|max:255',
            'role' => 'nullable|string|max:100',
            'visibility' => 'nullable|in:public,authenticated,hidden',
        ]);

        // If setting as featured, unfeature all other media
        if ($request->input('is_featured')) {
            Media::where('item_id', $media->item_id)
                ->update(['is_featured' => false]);
        }

        // Handle metadata for supplemental files
        $metadata = $media->metadata ?? [];
        
        if ($request->input('media_type') === 'supplemental') {
            // Store supplemental metadata
            $metadata['label'] = $request->input('label', $media->filename);
            $metadata['role'] = $request->input('role', 'supplemental');
            $metadata['visibility'] = $request->input('visibility', 'public');
        } else {
            // Switching to main - validate MIME type matches item type
            $item = $media->item;
            $allowedMimeTypes = $this->getAllowedMimeTypesForItemType($item->item_type);
            $mimeCategory = explode('/', $media->mime_type)[0]; // e.g., 'image', 'audio', 'video'
            
            if (!in_array($mimeCategory, $allowedMimeTypes) && !in_array($media->mime_type, $allowedMimeTypes)) {
                return response()->json([
                    'error' => "Cannot set as main media. File type \"{$media->mime_type}\" is not compatible with item type \"{$item->item_type}\". Expected: " . implode(', ', $allowedMimeTypes) . '.'
                ], 422);
            }
            
            // Clear supplemental metadata if switching to main
            unset($metadata['label'], $metadata['role'], $metadata['visibility']);
        }

        $media->update([
            'alt_text' => $request->input('alt_text'),
            'is_featured' => $request->input('is_featured', $media->is_featured),
            'metadata' => $metadata,
        ]);

        $item = $media->item;
        $item->load('media');

        // Return the full media list
        return view('items._media_list', compact('item'));
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
        return view('items._media_list', compact('item'));
    }

    /**
     * Reorder media files.
     */
    public function reorder(Request $request, Item $item)
    {
        $request->validate([
            'order' => 'required|array',
        ]);

        $mediaIds = $request->input('order');

        foreach ($mediaIds as $index => $mediaId) {
            Media::where('id', $mediaId)
                ->where('item_id', $item->id)
                ->update(['sort_order' => $index]);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Handle chunked file upload for large files.
     */
    public function uploadChunk(Request $request, Item $item)
    {
        $this->authorize('update', $item);
        
        $chunkIndex = $request->input('dzchunkindex');
        $totalChunks = $request->input('dztotalchunkcount');
        $uuid = $request->input('dzuuid');
        $filename = $request->input('original_filename');
        
        // Create temp directory for chunks
        $tempDir = storage_path("app/temp/chunks/{$uuid}");
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }
        
        // Save chunk
        $chunk = $request->file('file');
        if (!$chunk) {
            return response()->json(['error' => 'No file chunk received'], 400);
        }
        
        $chunk->move($tempDir, $chunkIndex);
        
        // Check if all chunks received
        $chunks = glob($tempDir . '/*');
        
        if (count($chunks) == $totalChunks) {
            // Combine all chunks
            $finalPath = storage_path("app/temp/{$uuid}_{$filename}");
            $output = fopen($finalPath, 'wb');
            
            for ($i = 0; $i < $totalChunks; $i++) {
                $chunkPath = $tempDir . '/' . $i;
                if (file_exists($chunkPath)) {
                    $content = file_get_contents($chunkPath);
                    fwrite($output, $content);
                    unlink($chunkPath);
                }
            }
            fclose($output);
            
            // Get file info before moving
            $mimeType = mime_content_type($finalPath);
            $fileSize = filesize($finalPath);
            
            // Determine disk
            $disk = config('media.disk', 'public');
            
            // Move to permanent storage using stream (memory efficient)
            $storagePath = "items/{$item->id}/" . \Illuminate\Support\Str::uuid() . '_' . basename($filename);
            $stream = fopen($finalPath, 'r');
            Storage::disk($disk)->put($storagePath, $stream);
            if (is_resource($stream)) {
                fclose($stream);
            }
            
            // Create media record
            $media = $item->media()->create([
                'filename' => $filename,
                'path' => $storagePath,
                'mime_type' => $mimeType,
                'size' => $fileSize,
                'sort_order' => $item->media()->max('sort_order') + 1,
                'processing_status' => 'uploaded',
            ]);
            
            // Dispatch processing job
            if (config('media.processing.enabled', true)) {
                ProcessMediaUpload::dispatch($media);
                
                Log::info("Dispatched media processing job (chunked upload)", [
                    'media_id' => $media->id,
                    'filename' => $filename,
                    'size' => $fileSize,
                ]);
            } else {
                $media->markAsReady();
            }
            
            // Cleanup
            unlink($finalPath);
            rmdir($tempDir);
            
            return response()->json([
                'success' => true,
                'media_id' => $media->id,
            ]);
        }
        
        return response()->json(['success' => true, 'chunk' => $chunkIndex]);
    }

    /**
     * Get allowed MIME type categories for a given item type.
     * Returns array of allowed MIME type prefixes (e.g., 'audio', 'video', 'image').
     */
    private function getAllowedMimeTypesForItemType(string $itemType): array
    {
        return match($itemType) {
            'audio' => ['audio'],
            'video' => ['video'],
            'image' => ['image'],
            'document' => ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'text'],
            'other' => ['audio', 'video', 'image', 'application', 'text'], // Allow anything for "other"
            default => [],
        };
    }
}
