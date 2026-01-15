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
            $existingTags = isset($item) ? $item->tags : collect();
        @endphp

        @if($tagsTaxonomy)
            <div class="mb-3">
                <label for="tag_input" class="form-label">Tags</label>
                
                {{-- Tag Badges Display --}}
                <div id="tag-badges" class="mb-2 d-flex flex-wrap gap-2">
                    @foreach($existingTags as $tag)
                        <span class="badge bg-primary d-flex align-items-center gap-1" data-tag-id="{{ $tag->id }}" data-tag-name="{{ $tag->name }}">
                            {{ $tag->name }}
                            <button type="button" class="btn-close btn-close-white" style="font-size: 0.65rem;" aria-label="Remove tag" onclick="removeTag(this)"></button>
                        </span>
                    @endforeach
                </div>

                {{-- Tag Input with Autocomplete --}}
                <div class="position-relative">
                    <input 
                        type="text" 
                        class="form-control" 
                        id="tag_input"
                        placeholder="Type to search or add tags..."
                        autocomplete="off"
                    >
                    <div id="tag-autocomplete" class="list-group position-absolute w-100 shadow-sm" style="z-index: 1000; max-height: 200px; overflow-y: auto; display: none;"></div>
                </div>
                
                {{-- Hidden input to store comma-separated tag names for form submission --}}
                <input type="hidden" name="tag_names" id="tag_names" value="{{ old('tag_names', $existingTags->pluck('name')->implode(', ')) }}" @if(isset($item)) form="item-edit-form" @endif>
                
                <div class="form-text small">
                    Type to search existing tags or create new ones. Press Enter or click to add.
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

<script>
(function() {
    const tagInput = document.getElementById('tag_input');
    const tagBadges = document.getElementById('tag-badges');
    const tagNamesInput = document.getElementById('tag_names');
    const autocompleteDiv = document.getElementById('tag-autocomplete');
    
    let allTags = []; // Will store all available tags
    let currentTags = new Set(); // Track current tag names (case-insensitive)
    
    // Initialize with existing tags
    document.querySelectorAll('#tag-badges .badge').forEach(badge => {
        currentTags.add(badge.dataset.tagName.toLowerCase());
    });
    
    // Fetch all available tags for autocomplete
    async function fetchTags() {
        try {
            const response = await fetch('/api/tags');
            if (response.ok) {
                allTags = await response.json();
            }
        } catch (error) {
            console.error('Failed to fetch tags:', error);
        }
    }
    
    // Filter tags based on input
    function filterTags(query) {
        const lowerQuery = query.toLowerCase().trim();
        if (!lowerQuery) return [];
        
        return allTags.filter(tag => 
            tag.name.toLowerCase().includes(lowerQuery) && 
            !currentTags.has(tag.name.toLowerCase())
        );
    }
    
    // Show autocomplete dropdown
    function showAutocomplete(tags, query) {
        autocompleteDiv.innerHTML = '';
        
        if (tags.length === 0) {
            if (query.trim()) {
                // Suggest creating a new tag
                const item = document.createElement('button');
                item.type = 'button';
                item.className = 'list-group-item list-group-item-action';
                item.innerHTML = `<i class="bi bi-plus-circle me-2"></i>Create new tag: "<strong>${escapeHtml(query.trim())}</strong>"`;
                item.onclick = () => addTag(query.trim());
                autocompleteDiv.appendChild(item);
            }
        } else {
            tags.forEach(tag => {
                const item = document.createElement('button');
                item.type = 'button';
                item.className = 'list-group-item list-group-item-action';
                item.textContent = tag.name;
                item.onclick = () => addTag(tag.name);
                autocompleteDiv.appendChild(item);
            });
        }
        
        autocompleteDiv.style.display = tags.length > 0 || query.trim() ? 'block' : 'none';
    }
    
    // Hide autocomplete
    function hideAutocomplete() {
        setTimeout(() => {
            autocompleteDiv.style.display = 'none';
        }, 200);
    }
    
    // Add tag as badge
    function addTag(tagName) {
        tagName = tagName.trim();
        if (!tagName || currentTags.has(tagName.toLowerCase())) {
            return;
        }
        
        currentTags.add(tagName.toLowerCase());
        
        // Create badge
        const badge = document.createElement('span');
        badge.className = 'badge bg-primary d-flex align-items-center gap-1';
        badge.dataset.tagName = tagName;
        badge.innerHTML = `
            ${escapeHtml(tagName)}
            <button type="button" class="btn-close btn-close-white" style="font-size: 0.65rem;" aria-label="Remove tag" onclick="removeTag(this)"></button>
        `;
        
        tagBadges.appendChild(badge);
        
        // Update hidden input
        updateHiddenInput();
        
        // Clear input
        tagInput.value = '';
        hideAutocomplete();
    }
    
    // Remove tag (global function)
    window.removeTag = function(button) {
        const badge = button.closest('.badge');
        const tagName = badge.dataset.tagName;
        currentTags.delete(tagName.toLowerCase());
        badge.remove();
        updateHiddenInput();
    };
    
    // Update hidden input with current tags
    function updateHiddenInput() {
        const tagNames = Array.from(document.querySelectorAll('#tag-badges .badge'))
            .map(badge => badge.dataset.tagName);
        tagNamesInput.value = tagNames.join(', ');
    }
    
    // HTML escape helper
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    // Event listeners
    tagInput.addEventListener('input', (e) => {
        const query = e.target.value;
        const filtered = filterTags(query);
        showAutocomplete(filtered, query);
    });
    
    tagInput.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            const query = tagInput.value.trim();
            if (query) {
                addTag(query);
            }
        } else if (e.key === 'Escape') {
            hideAutocomplete();
        }
    });
    
    tagInput.addEventListener('focus', () => {
        const query = tagInput.value;
        if (query) {
            const filtered = filterTags(query);
            showAutocomplete(filtered, query);
        }
    });
    
    tagInput.addEventListener('blur', hideAutocomplete);
    
    // Fetch tags on load
    fetchTags();
})();
</script>
