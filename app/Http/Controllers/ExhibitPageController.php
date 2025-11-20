<?php

namespace App\Http\Controllers;

use App\Models\Exhibit;
use App\Models\ExhibitPage;
use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class ExhibitPageController extends Controller
{
    use AuthorizesRequests;
    /**
     * Display a listing of pages for an exhibit.
     */
    public function index(Exhibit $exhibit)
    {
        $pages = $exhibit->pages()->with('children')->get();
        
        return view('exhibits.pages.index', compact('exhibit', 'pages'));
    }

    /**
     * Show the form for creating a new page.
     */
    public function create(Exhibit $exhibit, Request $request)
    {
        $this->authorize('create', [ExhibitPage::class, $exhibit]);
        
        $parentId = $request->query('parent_id');
        $parent = $parentId ? ExhibitPage::findOrFail($parentId) : null;
        
        return view('exhibits.pages.create', compact('exhibit', 'parent'));
    }

    /**
     * Store a newly created page in storage.
     */
    public function store(Request $request, Exhibit $exhibit)
    {
        $this->authorize('create', [ExhibitPage::class, $exhibit]);
        
        $validated = $request->validate([
            'parent_id' => 'nullable|exists:exhibit_pages,id',
            'title' => 'required|string|max:255',
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('exhibit_pages')->where('exhibit_id', $exhibit->id)
            ],
            'visibility' => 'required|in:public,authenticated,hidden',
            'content' => 'nullable|string',
            'layout_blocks' => 'nullable|array',
        ]);

        // Handle slug generation
        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['title']);
        }

        // Set sort order to be last
        $maxSortOrder = $exhibit->pages()
            ->where('parent_id', $validated['parent_id'] ?? null)
            ->max('sort_order') ?? -1;
        
        $validated['sort_order'] = $maxSortOrder + 1;
        $validated['exhibit_id'] = $exhibit->id;

        $page = ExhibitPage::create($validated);

        return redirect()->route('exhibits.pages.show', [$exhibit, $page])
            ->with('success', 'Page created successfully.');
    }

    /**
     * Display the specified page.
     */
    public function show(Exhibit $exhibit, ExhibitPage $page)
    {
        $this->authorize('view', $page);
        
        $page->load(['children', 'items', 'parent']);
        
        return view('exhibits.pages.show', compact('exhibit', 'page'));
    }

    /**
     * Show the form for editing the specified page.
     */
    public function edit(Exhibit $exhibit, ExhibitPage $page)
    {
        $this->authorize('update', $page);
        
        $page->load('items');
        $availableItems = Item::orderBy('title')->get();
        
        return view('exhibits.pages.edit', compact('exhibit', 'page', 'availableItems'));
    }

    /**
     * Update the specified page in storage.
     */
    public function update(Request $request, Exhibit $exhibit, ExhibitPage $page)
    {
        $this->authorize('update', $page);
        
        $validated = $request->validate([
            'parent_id' => 'nullable|exists:exhibit_pages,id',
            'title' => 'required|string|max:255',
            'slug' => [
                'required',
                'string',
                'max:255',
                Rule::unique('exhibit_pages')->where('exhibit_id', $exhibit->id)->ignore($page->id)
            ],
            'visibility' => 'required|in:public,authenticated,hidden',
            'content' => 'nullable|string',
            'layout_blocks' => 'nullable|array',
        ]);

        $page->update($validated);

        return redirect()->route('exhibits.pages.show', [$exhibit, $page])
            ->with('success', 'Page updated successfully.');
    }

    /**
     * Remove the specified page from storage.
     */
    public function destroy(Exhibit $exhibit, ExhibitPage $page)
    {
        $this->authorize('delete', $page);
        
        $page->delete();

        return redirect()->route('exhibits.show', $exhibit)
            ->with('success', 'Page deleted successfully.');
    }

    /**
     * Attach an item to a page (HTMX endpoint)
     */
    public function attachItem(Request $request, Exhibit $exhibit, ExhibitPage $page)
    {
        $validated = $request->validate([
            'item_id' => 'required|exists:items,id',
            'caption' => 'nullable|string',
            'layout_position' => 'required|in:full,left,right,gallery',
        ]);

        $nextSortOrder = $page->items()->max('sort_order') + 1;

        $page->items()->attach($validated['item_id'], [
            'sort_order' => $nextSortOrder,
            'caption' => $validated['caption'] ?? null,
            'layout_position' => $validated['layout_position'] ?? 'full',
        ]);

        $page->load('items');

        return view('exhibits.pages.partials.items_list', compact('page'));
    }

    /**
     * Update item attachment details (HTMX endpoint)
     */
    public function updateItem(Request $request, Exhibit $exhibit, ExhibitPage $page, Item $item)
    {
        $validated = $request->validate([
            'caption' => 'nullable|string',
            'layout_position' => 'required|in:full,left,right,gallery',
        ]);

        $page->items()->updateExistingPivot($item->id, $validated);
        $page->load('items');

        return view('exhibits.pages.partials.items_list', compact('page'));
    }

    /**
     * Detach an item from a page (HTMX endpoint)
     */
    public function detachItem(Exhibit $exhibit, ExhibitPage $page, Item $item)
    {
        $page->items()->detach($item->id);
        $page->load('items');

        return view('exhibits.pages.partials.items_list', compact('page'));
    }

    /**
     * Reorder pages (HTMX endpoint)
     */
    public function reorder(Request $request, Exhibit $exhibit)
    {
        $validated = $request->validate([
            'pages' => 'required|array',
            'pages.*' => 'exists:exhibit_pages,id',
        ]);

        foreach ($validated['pages'] as $index => $pageId) {
            ExhibitPage::where('id', $pageId)->update(['sort_order' => $index]);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Reorder items on a page (HTMX endpoint)
     */
    public function reorderItems(Request $request, Exhibit $exhibit, ExhibitPage $page)
    {
        $validated = $request->validate([
            'items' => 'required|array',
            'items.*' => 'exists:items,id',
        ]);

        foreach ($validated['items'] as $index => $itemId) {
            $page->items()->updateExistingPivot($itemId, [
                'sort_order' => $index,
            ]);
        }

        return response()->json(['success' => true]);
    }
}
