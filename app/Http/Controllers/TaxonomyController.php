<?php

namespace App\Http\Controllers;

use App\Models\Taxonomy;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class TaxonomyController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of taxonomies.
     */
    public function index()
    {
        // Only admins can manage taxonomies
        if (!Auth::user()->isAdmin()) {
            abort(403);
        }

        $taxonomies = Taxonomy::withCount('terms')->get();

        return view('admin.taxonomies.index', compact('taxonomies'));
    }

    /**
     * Show the form for creating a new taxonomy.
     */
    public function create()
    {
        if (!Auth::user()->isAdmin()) {
            abort(403);
        }

        return view('admin.taxonomies.create');
    }

    /**
     * Store a newly created taxonomy.
     */
    public function store(Request $request)
    {
        if (!Auth::user()->isAdmin()) {
            abort(403);
        }

        $validated = $request->validate([
            'key' => 'required|string|max:255|unique:taxonomies,key|alpha_dash',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'hierarchical' => 'nullable|boolean',
        ]);

        $validated['hierarchical'] = $request->boolean('hierarchical');

        Taxonomy::create($validated);

        return redirect()->route('admin.taxonomies.index')
            ->with('success', 'Taxonomy created successfully.');
    }

    /**
     * Show the form for editing a taxonomy.
     */
    public function edit(Taxonomy $taxonomy)
    {
        if (!Auth::user()->isAdmin()) {
            abort(403);
        }

        return view('admin.taxonomies.edit', compact('taxonomy'));
    }

    /**
     * Update the specified taxonomy.
     */
    public function update(Request $request, Taxonomy $taxonomy)
    {
        if (!Auth::user()->isAdmin()) {
            abort(403);
        }

        $validated = $request->validate([
            'key' => 'required|string|max:255|alpha_dash|unique:taxonomies,key,' . $taxonomy->id,
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'hierarchical' => 'nullable|boolean',
        ]);

        $validated['hierarchical'] = $request->boolean('hierarchical');

        $taxonomy->update($validated);

        return redirect()->route('admin.taxonomies.index')
            ->with('success', 'Taxonomy updated successfully.');
    }

    /**
     * Remove the specified taxonomy.
     */
    public function destroy(Taxonomy $taxonomy)
    {
        if (!Auth::user()->isAdmin()) {
            abort(403);
        }

        // Prevent deletion of the default tags taxonomy
        if ($taxonomy->key === 'tags') {
            return redirect()->route('admin.taxonomies.index')
                ->with('error', 'Cannot delete the default Tags taxonomy.');
        }

        $taxonomy->delete();

        return redirect()->route('admin.taxonomies.index')
            ->with('success', 'Taxonomy deleted successfully.');
    }
}
