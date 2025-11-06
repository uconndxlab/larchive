<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Metadata;
use App\Models\Concerns\DublinCore;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Controller for managing Dublin Core and custom metadata on items.
 */
class MetadataController extends Controller
{
    /**
     * Display metadata list for an item (HTMX partial).
     */
    public function index(Item $item)
    {
        $item->load('metadata');
        return view('metadata._list', compact('item'));
    }

    /**
     * Store new metadata for an item.
     */
    public function store(Request $request, Item $item)
    {
        $validated = $request->validate([
            'key' => ['required', 'string', Rule::in(DublinCore::ALL_FIELDS)],
            'value' => 'required|string|max:5000',
        ]);

        // Use the helper to set DC metadata
        $item->setDC($validated['key'], $validated['value']);

        $item->load('metadata');
        return view('metadata._list', compact('item'));
    }

    /**
     * Update existing metadata.
     */
    public function update(Request $request, Metadata $metadata)
    {
        $validated = $request->validate([
            'value' => 'required|string|max:5000',
        ]);

        $metadata->update($validated);

        $item = $metadata->item;
        $item->load('metadata');
        return view('metadata._row', compact('metadata', 'item'));
    }

    /**
     * Delete metadata.
     */
    public function destroy(Metadata $metadata)
    {
        $item = $metadata->item;
        $metadata->delete();

        $item->load('metadata');
        return view('metadata._list', compact('item'));
    }
}
