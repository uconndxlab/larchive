<?php

namespace App\Http\Controllers;

use App\Models\Collection;
use App\Http\Controllers\Concerns\SyncsTerms;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class CollectionController extends Controller
{
    use AuthorizesRequests, SyncsTerms;
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $query = Collection::published()->visibleTo(Auth::user());

        if (request('search')) {
            $query->where('title', 'like', '%' . request('search') . '%');
        }

        $collections = $query->latest()->paginate(20);

        // Return partial for HTMX requests
        if (request()->header('HX-Request')) {
            return view('collections._table', compact('collections'));
        }

        return view('collections.index', compact('collections'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->authorize('create', Collection::class);
        
        return view('collections.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('create', Collection::class);
        
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:collections,slug',
            'description' => 'nullable|string',
            'visibility' => 'required|in:public,authenticated,hidden',
            'status' => 'required|in:draft,in_review,published,archived',
        ]);

        $validated['slug'] = $validated['slug'] ?? Str::slug($validated['title']);
        
        // Set published_at based on status
        $validated['published_at'] = $validated['status'] === 'published' ? now() : null;

        $collection = Collection::create($validated);

        // Sync taxonomy terms
        $this->syncTerms($collection, $request);

        return redirect()->route('collections.index')
            ->with('success', 'Collection created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Collection $collection)
    {
        $this->authorize('view', $collection);
        
        // Load only published items for public view
        $collection->load(['items' => function ($query) {
            $query->published()->visibleTo(Auth::user());
        }]);
        return view('collections.show', compact('collection'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Collection $collection)
    {
        $this->authorize('update', $collection);
        
        $collection->load(['creator', 'updater']);
        return view('collections.edit', compact('collection'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Collection $collection)
    {
        $this->authorize('update', $collection);
        
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:collections,slug,' . $collection->id,
            'description' => 'nullable|string',
            'visibility' => 'required|in:public,authenticated,hidden',
            'status' => 'required|in:draft,in_review,published,archived',
        ]);

        $validated['slug'] = $validated['slug'] ?? Str::slug($validated['title']);
        
        // Update published_at based on status transitions
        if ($validated['status'] === 'published' && $collection->status !== 'published') {
            $validated['published_at'] = now();
        } elseif ($validated['status'] !== 'published') {
            $validated['published_at'] = null;
        }

        $collection->update($validated);

        // Sync taxonomy terms
        $this->syncTerms($collection, $request);

        return redirect()->route('collections.index')
            ->with('success', 'Collection updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Collection $collection)
    {
        $this->authorize('delete', $collection);
        
        $collection->delete();

        return redirect()->route('collections.index')
            ->with('success', 'Collection deleted successfully.');
    }
}
