{{-- 
    Taxonomy/Term selector for resources
    Usage: @include('partials._taxonomy_selector', ['resource' => $item])
    
    This partial provides:
    - A tags input field where admins can type new tags or select existing ones
    - Multi-select dropdowns for other taxonomies
--}}

@php
    $allTaxonomies = \App\Models\Taxonomy::with('terms')->get();
    $tagsTaxonomy = $allTaxonomies->firstWhere('key', 'tags');
    $otherTaxonomies = $allTaxonomies->where('key', '!=', 'tags');
    
    // Get currently selected term IDs for this resource
    $selectedTermIds = isset($resource) ? $resource->terms->pluck('id')->toArray() : [];
@endphp

<div class="card mb-3">
    <div class="card-header">
        <h6 class="mb-0">Taxonomies & Tags</h6>
    </div>
    <div class="card-body">
        
        {{-- Tags (special handling for free-form input) --}}
        @if($tagsTaxonomy)
            <div class="mb-3">
                <label for="tag_names" class="form-label">Tags</label>
                <input 
                    type="text" 
                    class="form-control" 
                    id="tag_names" 
                    name="tag_names" 
                    value="{{ old('tag_names', isset($resource) ? $resource->tags->pluck('name')->implode(', ') : '') }}"
                    placeholder="Enter tags separated by commas"
                >
                <div class="form-text">
                    Type tags separated by commas. New tags will be created automatically.
                </div>
            </div>
        @endif

        {{-- Other taxonomies --}}
        @foreach($otherTaxonomies as $taxonomy)
            <div class="mb-3">
                <label for="taxonomy_{{ $taxonomy->id }}" class="form-label">{{ $taxonomy->name }}</label>
                <select 
                    class="form-select" 
                    id="taxonomy_{{ $taxonomy->id }}" 
                    name="taxonomy_terms[{{ $taxonomy->id }}][]"
                    multiple
                    size="5"
                >
                    @foreach($taxonomy->terms as $term)
                        <option 
                            value="{{ $term->id }}"
                            {{ in_array($term->id, old('taxonomy_terms.' . $taxonomy->id, $selectedTermIds)) ? 'selected' : '' }}
                        >
                            {{ $term->parent ? '— ' : '' }}{{ $term->name }}
                        </option>
                    @endforeach
                </select>
                <div class="form-text">
                    Hold Ctrl (Cmd on Mac) to select multiple {{ strtolower($taxonomy->name) }}.
                </div>
            </div>
        @endforeach

        @if($otherTaxonomies->isEmpty() && !$tagsTaxonomy)
            <p class="text-muted mb-0">
                No taxonomies available. 
                @auth
                    @if(auth()->user()->isAdmin())
                        <a href="{{ route('admin.taxonomies.create') }}">Create one</a>
                    @endif
                @endauth
            </p>
        @endif
        
    </div>
</div>
