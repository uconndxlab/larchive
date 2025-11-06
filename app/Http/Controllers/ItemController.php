<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Collection;
use App\Models\Concerns\DublinCore;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ItemController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $query = Item::with('collection');

        if (request('search')) {
            $query->where('title', 'like', '%' . request('search') . '%');
        }

        if (request('collection_id')) {
            $query->where('collection_id', request('collection_id'));
        }

        $items = $query->latest()->paginate(20);

        // Return partial for HTMX requests
        if (request()->header('HX-Request')) {
            return view('items._table', compact('items'));
        }

        return view('items.index', compact('items'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $collections = Collection::orderBy('title')->get();
        return view('items.create', compact('collections'));
    }

    /**
     * HTMX partial for transcript upload field (conditional on item_type).
     */
    public function transcriptField(Request $request)
    {
        $itemType = $request->input('item_type', 'other');
        return view('items._transcript_upload', compact('itemType'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'collection_id' => 'nullable|exists:collections,id',
            'item_type' => ['required', Rule::in(['audio', 'video', 'image', 'document', 'other'])],
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:items,slug',
            'description' => 'nullable|string',
            'extra' => 'nullable|json',
            'publish_now' => 'nullable|boolean',
            // Transcript upload (optional, for audio/video types)
            'transcript' => 'nullable|file|mimes:txt,vtt,srt,pdf,doc,docx|max:10240',
            // Dublin Core metadata
            'dc_creator' => 'nullable|string|max:500',
            'dc_date' => 'nullable|date_format:Y-m-d',
            'dc_subject' => 'nullable|string|max:500',
            'dc_language' => 'nullable|string|max:10',
            'dc_rights' => 'nullable|string|max:500',
        ]);

        $validated['slug'] = $validated['slug'] ?? Str::slug($validated['title']);
        $validated['published_at'] = $request->boolean('publish_now') ? now() : null;
        unset($validated['publish_now']);

        $item = Item::create($validated);

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

        return redirect()->route('items.edit', $item)
            ->with('success', 'Item created successfully. You can now add media files.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Item $item)
    {
        $item->load(['collection', 'media', 'metadata']);
        return view('items.show', compact('item'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Item $item)
    {
        $collections = Collection::orderBy('title')->get();
        return view('items.edit', compact('item', 'collections'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Item $item)
    {
        $validated = $request->validate([
            'collection_id' => 'nullable|exists:collections,id',
            'item_type' => ['required', Rule::in(['audio', 'video', 'image', 'document', 'other'])],
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:items,slug,' . $item->id,
            'description' => 'nullable|string',
            'extra' => 'nullable|json',
            'publish_now' => 'nullable|boolean',
            // Transcript upload (optional, for audio/video types)
            'transcript' => 'nullable|file|mimes:txt,vtt,srt,pdf,doc,docx|max:10240',
            // Dublin Core metadata
            'dc_creator' => 'nullable|string|max:500',
            'dc_date' => 'nullable|date_format:Y-m-d',
            'dc_subject' => 'nullable|string|max:500',
            'dc_language' => 'nullable|string|max:10',
            'dc_rights' => 'nullable|string|max:500',
        ]);

        $validated['slug'] = $validated['slug'] ?? Str::slug($validated['title']);
        $validated['published_at'] = $request->boolean('publish_now') ? now() : null;
        unset($validated['publish_now']);

        $item->update($validated);

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
     * Remove the specified resource from storage.
     */
    public function destroy(Item $item)
    {
        $item->delete();

        return redirect()->route('items.index')
            ->with('success', 'Item deleted successfully.');
    }
}
