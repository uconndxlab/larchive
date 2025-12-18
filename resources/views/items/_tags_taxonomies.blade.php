{{-- Tags & Taxonomies --}}
<div class="card mb-3">
    <div class="card-header">
        <h6 class="mb-0">Tags & Taxonomies</h6>
    </div>
    <div class="card-body">
        @php
            $allTaxonomies = \App\Models\Taxonomy::with('terms')->get();
            $tagsTaxonomy = $allTaxonomies->firstWhere('key', 'tags');
            $otherTaxonomies = $allTaxonomies->where('key', '!=', 'tags');
            $selectedTermIds = isset($item) ? $item->terms->pluck('id')->toArray() : [];
        @endphp

        @if($tagsTaxonomy)
            <div class="mb-3">
                <label for="tag_names" class="form-label">Tags</label>
                <input 
                    type="text" 
                    class="form-control" 
                    id="tag_names" 
                    name="tag_names" 
                    value="{{ old('tag_names', isset($item) ? $item->tags->pluck('name')->implode(', ') : '') }}"
                    placeholder="Enter tags separated by commas"
                    @if(isset($item)) form="item-edit-form" @endif
                >
                <div class="form-text small">
                    Use tags to group items by theme or topic. New tags created automatically.
                </div>
            </div>
        @endif

        @foreach($otherTaxonomies as $taxonomy)
            <div class="mb-3">
                <label for="taxonomy_{{ $taxonomy->id }}" class="form-label">{{ $taxonomy->name }}</label>
                <select 
                    class="form-select" 
                    id="taxonomy_{{ $taxonomy->id }}" 
                    name="taxonomy_terms[{{ $taxonomy->id }}][]"
                    multiple
                    size="4"
                    @if(isset($item)) form="item-edit-form" @endif
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
                <div class="form-text small">
                    Hold Ctrl (Cmd) to select multiple.
                </div>
            </div>
        @endforeach

        @if($otherTaxonomies->isEmpty() && !$tagsTaxonomy)
            <p class="text-muted small mb-0">
                No taxonomies available.
            </p>
        @endif
    </div>
</div>
