<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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
     * Store multiple uploaded files.
     */
    public function store(Request $request, Item $item)
    {
        $request->validate([
            'files' => 'required|array',
            'files.*' => 'required|file|max:524288|mimes:jpg,jpeg,png,gif,svg,webp,pdf,doc,docx,xls,xlsx,ppt,pptx,txt,csv,mp3,mp4,wav,avi,mov,zip',
            'alt_text' => 'nullable|string|max:255',
        ]);

        $files = $request->file('files');
        $altText = $request->input('alt_text');

        // Get current max sort_order
        $maxSort = $item->media()->max('sort_order') ?? -1;

        foreach ($files as $index => $file) {
            $originalName = $file->getClientOriginalName();
            $path = $file->store("items/{$item->id}", 'public');
            $mimeType = $file->getMimeType();
            $size = $file->getSize();

            // Extract image dimensions if image
            $width = null;
            $height = null;
            if (str_starts_with($mimeType, 'image/')) {
                $fullPath = Storage::disk('public')->path($path);
                $dimensions = @getimagesize($fullPath);
                if ($dimensions) {
                    $width = $dimensions[0];
                    $height = $dimensions[1];
                }
            }

            $item->media()->create([
                'filename' => $originalName,
                'path' => $path,
                'mime_type' => $mimeType,
                'size' => $size,
                'alt_text' => $altText,
                'sort_order' => ++$maxSort,
                'meta' => $width && $height ? ['width' => $width, 'height' => $height] : null,
            ]);
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
