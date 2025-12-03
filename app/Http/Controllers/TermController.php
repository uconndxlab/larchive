<?php

namespace App\Http\Controllers;

use App\Models\Taxonomy;
use App\Models\Term;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class TermController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of terms for a taxonomy.
     */
    public function index(Taxonomy $taxonomy)
    {
        if (!Auth::user()->isAdmin()) {
            abort(403);
        }

        $terms = $taxonomy->terms()
            ->with('parent')
            ->orderBy('name')
            ->get();

        return view('admin.terms.index', compact('taxonomy', 'terms'));
    }

    /**
     * Show the form for creating a new term.
     */
    public function create(Taxonomy $taxonomy)
    {
        if (!Auth::user()->isAdmin()) {
            abort(403);
        }

        $parentTerms = $taxonomy->hierarchical 
            ? $taxonomy->terms()->whereNull('parent_id')->orderBy('name')->get()
            : collect();

        return view('admin.terms.create', compact('taxonomy', 'parentTerms'));
    }

    /**
     * Store a newly created term.
     */
    public function store(Request $request, Taxonomy $taxonomy)
    {
        if (!Auth::user()->isAdmin()) {
            abort(403);
        }

        $rules = [
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|alpha_dash',
            'description' => 'nullable|string',
        ];

        if ($taxonomy->hierarchical) {
            $rules['parent_id'] = 'nullable|exists:terms,id';
        }

        $validated = $request->validate($rules);
        $validated['taxonomy_id'] = $taxonomy->id;

        // Auto-generate slug if not provided
        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        // Ensure unique slug within taxonomy
        $slug = $validated['slug'];
        $originalSlug = $slug;
        $counter = 1;
        
        while (Term::where('taxonomy_id', $taxonomy->id)
            ->where('slug', $slug)
            ->exists()) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }
        
        $validated['slug'] = $slug;

        Term::create($validated);

        return redirect()->route('admin.terms.index', $taxonomy)
            ->with('success', 'Term created successfully.');
    }

    /**
     * Show the form for editing a term.
     */
    public function edit(Taxonomy $taxonomy, Term $term)
    {
        if (!Auth::user()->isAdmin()) {
            abort(403);
        }

        // Ensure term belongs to taxonomy
        if ($term->taxonomy_id !== $taxonomy->id) {
            abort(404);
        }

        $parentTerms = $taxonomy->hierarchical 
            ? $taxonomy->terms()->whereNull('parent_id')->where('id', '!=', $term->id)->orderBy('name')->get()
            : collect();

        return view('admin.terms.edit', compact('taxonomy', 'term', 'parentTerms'));
    }

    /**
     * Update the specified term.
     */
    public function update(Request $request, Taxonomy $taxonomy, Term $term)
    {
        if (!Auth::user()->isAdmin()) {
            abort(403);
        }

        // Ensure term belongs to taxonomy
        if ($term->taxonomy_id !== $taxonomy->id) {
            abort(404);
        }

        $rules = [
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|alpha_dash',
            'description' => 'nullable|string',
        ];

        if ($taxonomy->hierarchical) {
            $rules['parent_id'] = 'nullable|exists:terms,id';
        }

        $validated = $request->validate($rules);

        // Ensure unique slug within taxonomy (excluding current term)
        $slugExists = Term::where('taxonomy_id', $taxonomy->id)
            ->where('slug', $validated['slug'])
            ->where('id', '!=', $term->id)
            ->exists();

        if ($slugExists) {
            return back()->withErrors(['slug' => 'This slug is already taken for this taxonomy.'])->withInput();
        }

        $term->update($validated);

        return redirect()->route('admin.terms.index', $taxonomy)
            ->with('success', 'Term updated successfully.');
    }

    /**
     * Remove the specified term.
     */
    public function destroy(Taxonomy $taxonomy, Term $term)
    {
        if (!Auth::user()->isAdmin()) {
            abort(403);
        }

        // Ensure term belongs to taxonomy
        if ($term->taxonomy_id !== $taxonomy->id) {
            abort(404);
        }

        $term->delete();

        return redirect()->route('admin.terms.index', $taxonomy)
            ->with('success', 'Term deleted successfully.');
    }

    /**
     * Public view: show all resources tagged with this term.
     */
    public function show(Taxonomy $taxonomy, Term $term)
    {
        // Ensure term belongs to taxonomy
        if ($term->taxonomy_id !== $taxonomy->id) {
            abort(404);
        }

        $user = Auth::user();

        // Get all items with this term, only published and respecting visibility
        $items = $term->items()
            ->published()
            ->visibleTo($user)
            ->with('collection', 'media')
            ->paginate(20, ['*'], 'items_page');

        // Get all collections with this term, only published
        $collections = $term->collections()
            ->published()
            ->visibleTo($user)
            ->withCount('items')
            ->paginate(20, ['*'], 'collections_page');

        // Get all exhibits with this term, only published
        $exhibits = $term->exhibits()
            ->published()
            ->visibleTo($user)
            ->paginate(20, ['*'], 'exhibits_page');

        return view('terms.show', compact('taxonomy', 'term', 'items', 'collections', 'exhibits'));
    }
}
