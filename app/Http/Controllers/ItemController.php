<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Collection;
use App\Models\Concerns\DublinCore;
use App\Http\Controllers\Concerns\SyncsTerms;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class ItemController extends Controller
{
    use AuthorizesRequests, SyncsTerms;
    
    /**
     * Display the admin workspace for items with status filtering.
     */
    public function workspace()
    {
        $this->authorize('viewAny', Item::class);

        $status = request('status', 'draft');
        $query = Item::with('collection');

        // Filter by status
        if (in_array($status, ['draft', 'in_review', 'published', 'archived'])) {
            $query->withStatus($status);
        }

        // Search filter
        if (request('search')) {
            $query->where('title', 'like', '%' . request('search') . '%');
        }

        // Collection filter
        if (request('collection_id')) {
            $query->where('collection_id', request('collection_id'));
        }

        $items = $query->latest()->paginate(20)->appends(request()->query());

        // Get collection options for filter
        $collections = Collection::orderBy('title')->get();

        // Count items by status for tabs
        $statusCounts = [
            'draft' => Item::where('status', 'draft')->count(),
            'in_review' => Item::where('status', 'in_review')->count(),
            'published' => Item::where('status', 'published')->count(),
            'archived' => Item::where('status', 'archived')->count(),
        ];

        return view('admin.items.workspace', compact('items', 'status', 'collections', 'statusCounts'));
    }
    
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $query = Item::with('collection')
            ->published()  // Only show published items
            ->visibleTo(Auth::user());

        if (request('search')) {
            $query->where('title', 'like', '%' . request('search') . '%');
        }

        if (request('collection_id')) {
            $query->where('collection_id', request('collection_id'));
        }

        // Filter by tag
        if (request('tag_id')) {
            $query->whereHas('terms', function($q) {
                $q->where('terms.id', request('tag_id'));
            });
        }

        $items = $query->latest()->paginate(20);

        // Get all tags for the filter dropdown
        $tags = \App\Models\Term::whereHas('taxonomy', fn($q) => $q->where('key', 'tags'))
            ->orderBy('name')
            ->get();

        // Return partial for HTMX requests
        if (request()->header('HX-Request')) {
            return view('items._table', compact('items'));
        }

        return view('items.index', compact('items', 'tags'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->authorize('create', Item::class);
        
        $collections = Collection::orderBy('title')->get();
        return view('items.create', compact('collections'));
    }

    /**
     * HTMX partial for transcript upload field (conditional on item_type).
     */
    public function transcriptField(Request $request)
    {
        $itemType = $request->input('item_type', 'other');
        $item = new \stdClass();
        $item->item_type = $itemType;
        $item->transcript = null;
        
        // Return empty if not audio/video
        if (!in_array($itemType, ['audio', 'video'])) {
            return response('', 200);
        }
        
        return view('items._transcript_section', compact('item'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('create', Item::class);
        
        $validated = $request->validate([
            'collection_id' => 'nullable|exists:collections,id',
            'item_type' => ['required', Rule::in(['audio', 'video', 'image', 'document', 'other'])],
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:items,slug',
            'description' => 'nullable|string',
            'visibility' => 'required|in:public,authenticated,hidden',
            'status' => 'required|in:draft,in_review,published,archived',
            'extra' => 'nullable|json',
            // Featured image upload
            'featured_image' => 'nullable|image|max:10240',
            // Transcript upload (optional, for audio/video types)
            'transcript' => 'nullable|file|mimes:txt,vtt,srt,pdf,doc,docx|max:10240',
            // Dublin Core metadata
            'dc_creator' => 'nullable|string|max:500',
            'dc_date' => 'nullable|date_format:Y-m-d',
            'dc_subject' => 'nullable|string|max:500',
            'dc_language' => 'nullable|string|max:10',
            'dc_rights' => 'nullable|string|max:500',
        ]);

        // Check if user can set this status
        if (in_array($validated['status'], ['published', 'archived'])) {
            $this->authorize('publish', Item::class);
        }

        $validated['slug'] = $validated['slug'] ?? Str::slug($validated['title']);
        
        // Set published_at based on status
        $validated['published_at'] = $validated['status'] === 'published' ? now() : null;

        $item = Item::create($validated);

        // Handle featured image upload
        if ($request->hasFile('featured_image')) {
            $file = $request->file('featured_image');
            // Store in public disk like other media
            $path = $file->store("items/{$item->id}/files", 'public');
            
            $featuredMedia = $item->media()->create([
                'filename' => $file->getClientOriginalName(),
                'path' => $path,
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
                'is_transcript' => false,
                'sort_order' => 0,
                'processing_status' => 'ready',
            ]);

            $item->update(['featured_image_id' => $featuredMedia->id]);
        }

        // Handle transcript upload
        if ($request->hasFile('transcript') && in_array($item->item_type, ['audio', 'video'])) {
            $file = $request->file('transcript');
            $path = $file->store("items/{$item->id}", 'public');
            
            $transcriptMedia = $item->media()->create([
                'filename' => $file->getClientOriginalName(),
                'path' => $path,
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
                'is_transcript' => true,
                'sort_order' => 999, // Put transcripts at end
            ]);

            // Link as primary transcript
            $item->update(['transcript_id' => $transcriptMedia->id]);
        }

        // Save Dublin Core metadata
        $this->saveDublinCoreMetadata($item, $request);

        // Sync taxonomy terms
        $this->syncTerms($item, $request);

        return redirect()->route('items.edit', $item)
            ->with('success', 'Item created successfully. You can now add media files.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Item $item)
    {
        $this->authorize('view', $item);
        
        $item->load(['collection', 'media', 'metadata', 'terms.taxonomy']);
        return view('items.show', compact('item'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Item $item)
    {
        $this->authorize('update', $item);
        
        $item->load(['creator', 'updater']);
        $collections = Collection::orderBy('title')->get();
        return view('items.edit', compact('item', 'collections'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Item $item)
    {
        $this->authorize('update', $item);
        
        $validated = $request->validate([
            'collection_id' => 'nullable|exists:collections,id',
            'item_type' => ['required', Rule::in(['audio', 'video', 'image', 'document', 'other'])],
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:items,slug,' . $item->id,
            'description' => 'nullable|string',
            'visibility' => 'required|in:public,authenticated,hidden',
            'status' => 'required|in:draft,in_review,published,archived',
            'extra' => 'nullable|json',
            // Featured image upload and removal
            'featured_image' => 'nullable|image|max:10240',
            'existing_featured_image_id' => 'nullable|exists:media,id',
            'remove_featured_image' => 'nullable|boolean',
            // Transcript upload (optional, for audio/video types)
            'transcript' => 'nullable|file|mimes:txt,vtt,srt,pdf,doc,docx|max:10240',
            // Dublin Core metadata
            'dc_creator' => 'nullable|string|max:500',
            'dc_date' => 'nullable|date_format:Y-m-d',
            'dc_subject' => 'nullable|string|max:500',
            'dc_language' => 'nullable|string|max:10',
            'dc_rights' => 'nullable|string|max:500',
        ]);

        // Check if user can change status to published/archived
        if (in_array($validated['status'], ['published', 'archived']) && $item->status !== $validated['status']) {
            $this->authorize('publish', $item);
        }

        $validated['slug'] = $validated['slug'] ?? Str::slug($validated['title']);
        
        // Update published_at based on status transitions
        if ($validated['status'] === 'published' && $item->status !== 'published') {
            // Transitioning to published: set timestamp
            $validated['published_at'] = now();
        } elseif ($validated['status'] !== 'published') {
            // Not published: clear timestamp
            $validated['published_at'] = null;
        }
        // If already published and staying published, keep existing published_at

        $item->update($validated);

        // Handle featured image removal
        if ($request->input('remove_featured_image')) {
            $item->update(['featured_image_id' => null]);
        }

        // Handle new featured image upload
        if ($request->hasFile('featured_image')) {
            $file = $request->file('featured_image');
            // Store in public disk like other media
            $path = $file->store("items/{$item->id}/files", 'public');
            
            $featuredMedia = $item->media()->create([
                'filename' => $file->getClientOriginalName(),
                'path' => $path,
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
                'is_transcript' => false,
                'sort_order' => 0,
                'processing_status' => 'ready',
            ]);

            $item->update(['featured_image_id' => $featuredMedia->id]);
        }

        // Handle selecting existing media as featured image
        if ($request->input('existing_featured_image_id')) {
            $item->update(['featured_image_id' => $request->input('existing_featured_image_id')]);
        }

        // Handle new transcript upload
        if ($request->hasFile('transcript') && in_array($item->item_type, ['audio', 'video'])) {
            // Delete old transcript if exists
            if ($item->transcript_id) {
                $oldTranscript = $item->transcript;
                if ($oldTranscript) {
                    Storage::disk('public')->delete($oldTranscript->path);
                    $oldTranscript->delete();
                }
            }

            $file = $request->file('transcript');
            $path = $file->store("items/{$item->id}", 'public');
            
            $transcriptMedia = $item->media()->create([
                'filename' => $file->getClientOriginalName(),
                'path' => $path,
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
                'is_transcript' => true,
                'sort_order' => 999,
            ]);

            $item->update(['transcript_id' => $transcriptMedia->id]);
        }

        // Save Dublin Core metadata
        $this->saveDublinCoreMetadata($item, $request);

        // Sync taxonomy terms
        $this->syncTerms($item, $request);

        return redirect()->route('items.edit', $item)
            ->with('success', 'Item updated successfully.');
    }

    /**
     * Save Dublin Core metadata from form inputs.
     */
    protected function saveDublinCoreMetadata(Item $item, Request $request): void
    {
        // dc.title always mirrors the item title
        $item->setDC('dc.title', $item->title);
        
        // dc.description mirrors description if present
        if ($item->description) {
            $item->setDC('dc.description', $item->description);
        }

        // Save other DC fields if provided
        if ($request->filled('dc_creator')) {
            $item->setDC('dc.creator', $request->input('dc_creator'));
        }

        if ($request->filled('dc_date')) {
            $item->setDC('dc.date', $request->input('dc_date'));
        }

        if ($request->filled('dc_subject')) {
            $item->setDC('dc.subject', $request->input('dc_subject'));
        }

        if ($request->filled('dc_language')) {
            $item->setDC('dc.language', $request->input('dc_language'));
        }

        if ($request->filled('dc_rights')) {
            $item->setDC('dc.rights', $request->input('dc_rights'));
        }
    }

    /**
     * Attach an unattached upload from the incoming directory.
     */
    public function attachIncoming(Request $request, Item $item)
    {
        $this->authorize('update', $item);

        $validated = $request->validate([
            'filename' => 'required|string',
            'attach_as' => 'required|in:main,supplemental',
            'label' => 'nullable|string|max:255',
            'role' => 'nullable|string|max:100',
            'visibility' => 'nullable|in:public,authenticated,hidden',
        ]);

        $filename = $validated['filename'];

        // Block path traversal
        if (str_contains($filename, '/') || str_contains($filename, '\\') || str_contains($filename, '..')) {
            return redirect()->back()
                ->with('error', 'Invalid filename: path traversal not allowed.');
        }

        // Construct source path
        $sourcePath = storage_path("app/public/items/{$item->id}/incoming/{$filename}");

        // Verify file exists and is readable
        if (!file_exists($sourcePath) || !is_readable($sourcePath)) {
            return redirect()->back()
                ->with('error', 'File not found or not readable.');
        }

        // Generate unique name and destination path
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        $uniqueName = Str::uuid() . '.' . $extension;
        $destRelativePath = "public/items/{$item->id}/files/{$uniqueName}";
        $destFullPath = storage_path("app/{$destRelativePath}");

        // Ensure destination directory exists
        $destDir = dirname($destFullPath);
        if (!is_dir($destDir)) {
            mkdir($destDir, 0755, true);
        }

        // Move the file
        if (!rename($sourcePath, $destFullPath)) {
            return redirect()->back()
                ->with('error', 'Failed to move file.');
        }

        // Detect MIME type
        $mimeType = mime_content_type($destFullPath) ?: 'application/octet-stream';
        $fileSize = filesize($destFullPath);

        if ($validated['attach_as'] === 'main') {
            // Validate MIME type matches item type
            $allowedMimeTypes = $this->getAllowedMimeTypesForItemType($item->item_type);
            $mimeCategory = explode('/', $mimeType)[0]; // e.g., 'image', 'audio', 'video'
            
            if (!in_array($mimeCategory, $allowedMimeTypes) && !in_array($mimeType, $allowedMimeTypes)) {
                // Move file back to incoming if validation fails
                rename($destFullPath, $sourcePath);
                
                return redirect()->back()
                    ->with('error', "File type \"{$mimeType}\" is not compatible with item type \"{$item->item_type}\". Expected: " . implode(', ', $allowedMimeTypes) . '.');
            }

            // Attach as main media
            $media = $item->media()->create([
                'filename' => $filename,
                'path' => $destRelativePath,
                'mime_type' => $mimeType,
                'size' => $fileSize,
                'is_transcript' => false,
                'sort_order' => $item->media()->max('sort_order') + 1,
                'processing_status' => 'uploaded', // TODO: Dispatch processing job here if needed
            ]);

            // TODO: Dispatch processing job
            // ProcessMediaUpload::dispatch($media);

            return redirect()->back()
                ->with('success', "File \"{$filename}\" attached as main media.");
        } else {
            // Attach as supplemental media with metadata
            $metadata = [
                'label' => $validated['label'] ?? $filename,
                'role' => $validated['role'] ?? 'supplemental',
                'visibility' => $validated['visibility'] ?? 'public',
            ];

            $media = $item->media()->create([
                'filename' => $filename,
                'path' => $destRelativePath,
                'mime_type' => $mimeType,
                'size' => $fileSize,
                'is_transcript' => false,
                'sort_order' => $item->media()->max('sort_order') + 1,
                'metadata' => $metadata,
                'processing_status' => 'ready', // Supplemental files don't need processing
            ]);

            return redirect()->back()
                ->with('success', "File \"{$filename}\" attached as supplemental media.");
        }
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

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Item $item)
    {
        $this->authorize('delete', $item);
        
        $item->delete();

        return redirect()->route('items.index')
            ->with('success', 'Item deleted successfully.');
    }
}
