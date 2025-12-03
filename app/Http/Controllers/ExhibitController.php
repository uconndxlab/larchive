<?php

namespace App\Http\Controllers;

use App\Models\Exhibit;
use App\Models\Item;
use App\Http\Controllers\Concerns\SyncsTerms;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class ExhibitController extends Controller
{
    use AuthorizesRequests, SyncsTerms;
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Exhibit::withCount('pages')->visibleTo(Auth::user());
        
        // Show trashed exhibits if requested (admin only)
        if ($request->get('trashed') === '1') {
            /** @var \App\Models\User|null $user */
            $user = Auth::user();
            if ($user && $user->isAdmin()) {
                $query->onlyTrashed();
            }
        }
        
        $exhibits = $query->orderBy('featured', 'desc')
            ->orderBy('sort_order')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('exhibits.index', compact('exhibits'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->authorize('create', Exhibit::class);
        
        return view('exhibits.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('create', Exhibit::class);
        
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:exhibits,slug',
            'description' => 'nullable|string',
            'credits' => 'nullable|string',
            'theme' => 'nullable|string|max:50',
            'visibility' => 'required|in:public,authenticated,hidden',
            'cover_image' => 'nullable|image|max:2048',
            'featured' => 'boolean',
            'published' => 'boolean',
        ]);

        // Auto-generate unique slug if not provided
        if (empty($validated['slug'])) {
            $slug = Str::slug($validated['title']);
            $originalSlug = $slug;
            $counter = 1;
            
            while (Exhibit::where('slug', $slug)->exists()) {
                $slug = $originalSlug . '-' . $counter;
                $counter++;
            }
            
            $validated['slug'] = $slug;
        }

        // Handle cover image upload
        if ($request->hasFile('cover_image')) {
            $path = $request->file('cover_image')->store('exhibits', 'public');
            $validated['cover_image'] = $path;
        }

        // Handle published status
        if (!empty($validated['published'])) {
            $validated['published_at'] = now();
        }
        unset($validated['published']);

        $exhibit = Exhibit::create($validated);

        // Sync taxonomy terms
        $this->syncTerms($exhibit, $request);

        return redirect()->route('exhibits.show', $exhibit)
            ->with('success', 'Exhibit created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Exhibit $exhibit)
    {
        $this->authorize('view', $exhibit);
        
        $exhibit->load(['topLevelPages.children', 'items']);
        
        return view('exhibits.show', compact('exhibit'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Exhibit $exhibit)
    {
        $this->authorize('update', $exhibit);
        
        return view('exhibits.edit', compact('exhibit'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Exhibit $exhibit)
    {
        $this->authorize('update', $exhibit);
        
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => ['required', 'string', 'max:255', Rule::unique('exhibits')->ignore($exhibit->id)],
            'description' => 'nullable|string',
            'credits' => 'nullable|string',
            'theme' => 'nullable|string|max:50',
            'visibility' => 'required|in:public,authenticated,hidden',
            'cover_image' => 'nullable|image|max:2048',
            'featured' => 'boolean',
            'sort_order' => 'nullable|integer',
            'published' => 'boolean',
        ]);

        // Handle cover image upload
        if ($request->hasFile('cover_image')) {
            // Delete old cover image if it exists
            if ($exhibit->cover_image && Storage::disk('public')->exists($exhibit->cover_image)) {
                Storage::disk('public')->delete($exhibit->cover_image);
            }
            
            $path = $request->file('cover_image')->store('exhibits', 'public');
            $validated['cover_image'] = $path;
        }

        // Handle published status
        if (isset($validated['published'])) {
            $validated['published_at'] = $validated['published'] ? now() : null;
        }
        unset($validated['published']);

        $exhibit->update($validated);

        // Sync taxonomy terms
        $this->syncTerms($exhibit, $request);

        return redirect()->route('exhibits.show', $exhibit)
            ->with('success', 'Exhibit updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Exhibit $exhibit)
    {
        $this->authorize('delete', $exhibit);
        
        // Delete cover image if it exists
        if ($exhibit->cover_image && Storage::disk('public')->exists($exhibit->cover_image)) {
            Storage::disk('public')->delete($exhibit->cover_image);
        }

        $exhibit->delete();

        return redirect()->route('exhibits.index')
            ->with('success', 'Exhibit deleted successfully.');
    }

    /**
     * Restore a soft-deleted exhibit
     */
    public function restore($id)
    {
        $exhibit = Exhibit::onlyTrashed()->findOrFail($id);
        $exhibit->restore();

        return redirect()->route('exhibits.show', $exhibit)
            ->with('success', 'Exhibit restored successfully.');
    }

    /**
     * Permanently delete an exhibit
     */
    public function forceDelete($id)
    {
        $exhibit = Exhibit::onlyTrashed()->findOrFail($id);
        
        // Delete cover image if it exists
        if ($exhibit->cover_image && Storage::disk('public')->exists($exhibit->cover_image)) {
            Storage::disk('public')->delete($exhibit->cover_image);
        }

        $exhibit->forceDelete();

        return redirect()->route('exhibits.index', ['trashed' => 1])
            ->with('success', 'Exhibit permanently deleted.');
    }

    /**
     * Attach items to exhibit (HTMX endpoint)
     */
    public function attachItem(Request $request, Exhibit $exhibit)
    {
        $validated = $request->validate([
            'item_id' => 'required|exists:items,id',
            'caption' => 'nullable|string',
        ]);

        $nextSortOrder = $exhibit->items()->max('sort_order') + 1;

        $exhibit->items()->attach($validated['item_id'], [
            'sort_order' => $nextSortOrder,
            'caption' => $validated['caption'] ?? null,
        ]);

        $exhibit->load('items');

        return view('exhibits.partials.items_list', compact('exhibit'));
    }

    /**
     * Detach item from exhibit (HTMX endpoint)
     */
    public function detachItem(Exhibit $exhibit, Item $item)
    {
        $exhibit->items()->detach($item->id);
        $exhibit->load('items');

        return view('exhibits.partials.items_list', compact('exhibit'));
    }

    /**
     * Reorder exhibit items (HTMX endpoint)
     */
    public function reorderItems(Request $request, Exhibit $exhibit)
    {
        $validated = $request->validate([
            'items' => 'required|array',
            'items.*' => 'exists:items,id',
        ]);

        foreach ($validated['items'] as $index => $itemId) {
            $exhibit->items()->updateExistingPivot($itemId, [
                'sort_order' => $index,
            ]);
        }

        return response()->json(['success' => true]);
    }
}

