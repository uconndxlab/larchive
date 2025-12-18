<div id="media-list">
    <div class="list-group" id="media-sortable">
        @forelse($item->media->sortBy('sort_order') as $media)
        <div class="list-group-item" data-media-id="{{ $media->id }}">
            <div class="row align-items-start">
                {{-- Drag Handle & Preview --}}
                <div class="col-auto">
                    <div class="d-flex align-items-center">
                        <div class="me-2" style="cursor: move;" title="Drag to reorder">
                            <i class="bi bi-grip-vertical text-muted"></i>
                        </div>
                        @if(str_starts_with($media->mime_type, 'image/'))
                            <img src="{{ Storage::url($media->path) }}" 
                                 alt="{{ $media->alt_text }}" 
                                 class="img-thumbnail" 
                                 style="max-height: 80px; max-width: 80px; object-fit: cover;">
                        @else
                            <div class="bg-light border rounded d-flex align-items-center justify-content-center" 
                                 style="width: 80px; height: 80px;">
                                <span class="badge bg-secondary">
                                    {{ strtoupper(pathinfo($media->filename, PATHINFO_EXTENSION)) }}
                                </span>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Details & Alt Text --}}
                <div class="col" style="min-width: 0;">
                    <div class="mb-2">
                        <strong class="d-inline-block text-truncate" style="max-width: 100%; vertical-align: middle;">{{ $media->filename }}</strong>
                        @if($media->is_featured)
                            <span class="badge bg-primary ms-2">Featured</span>
                        @endif
                        @if(isset($media->metadata['role']))
                            <span class="badge bg-secondary ms-2">
                                {{ ucfirst($media->metadata['role']) }}
                            </span>
                            @if(isset($media->metadata['visibility']) && $media->metadata['visibility'] !== 'public')
                                <span class="badge bg-warning ms-1">
                                    {{ ucfirst($media->metadata['visibility']) }}
                                </span>
                            @endif
                        @endif
                    </div>
                    
                    <div class="mb-2">
                        <small class="text-muted">
                            {{ number_format($media->size / 1024, 1) }} KB
                            @if($media->meta && isset($media->meta['width'], $media->meta['height']))
                                • {{ $media->meta['width'] }}×{{ $media->meta['height'] }}
                            @endif
                            • {{ $media->mime_type }}
                        </small>
                    </div>

                    {{-- Combined Edit Form --}}
                    <form 
                        hx-patch="{{ route('media.update', $media) }}" 
                        hx-trigger="change from:.auto-save-field delay:500ms"
                        hx-swap="none"
                        hx-indicator=".save-indicator-{{ $media->id }}"
                        class="media-edit-form"
                        data-media-id="{{ $media->id }}"
                    >
                        @csrf
                        <div class="save-indicator-{{ $media->id }} htmx-indicator position-absolute top-0 end-0 mt-2 me-2">
                            <span class="badge bg-success">
                                <span class="spinner-border spinner-border-sm me-1" role="status"></span>
                                Saving...
                            </span>
                        </div>
                        
                        {{-- Alt Text Field --}}
                        <div class="input-group input-group-sm mb-2">
                            <span class="input-group-text">Alt Text</span>
                            <input 
                                type="text" 
                                name="alt_text" 
                                class="form-control auto-save-field" 
                                value="{{ $media->alt_text }}"
                                placeholder="Describe this media for accessibility..."
                            >
                        </div>

                        {{-- Media Type Selection --}}
                        @php
                            // Check if media MIME type is compatible with item type
                            $mimeCategory = explode('/', $media->mime_type)[0];
                            $allowedTypes = match($item->item_type) {
                                'audio' => ['audio'],
                                'video' => ['video'],
                                'image' => ['image'],
                                'document' => ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'text'],
                                'other' => ['audio', 'video', 'image', 'application', 'text'],
                                default => [],
                            };
                            $canBeMain = in_array($mimeCategory, $allowedTypes) || in_array($media->mime_type, $allowedTypes);
                            
                            // Determine current type: if has role metadata, it's supplemental
                            $isSupplemental = isset($media->metadata['role']);
                        @endphp
                        <div class="row g-2 mb-2">
                            <div class="col-md-4">
                                <select name="media_type" class="form-select form-select-sm auto-save-field media-type-select">
                                    <option value="main" 
                                            {{ (!$isSupplemental && $canBeMain) ? 'selected' : '' }}
                                            {{ !$canBeMain ? 'disabled' : '' }}>
                                        Main Media{{ !$canBeMain ? ' (incompatible)' : '' }}
                                    </option>
                                    <option value="supplemental" {{ $isSupplemental ? 'selected' : '' }}>Supplemental</option>
                                </select>
                            </div>

                            {{-- Supplemental Fields (show when supplemental is selected) --}}
                            <div class="col-md-8 supplemental-fields {{ !$isSupplemental ? 'd-none' : '' }}">
                                <div class="row g-2">
                                    <div class="col-md-6">
                                        <input 
                                            type="text" 
                                            name="label" 
                                            class="form-control form-control-sm auto-save-field" 
                                            value="{{ $media->metadata['label'] ?? '' }}"
                                            placeholder="Label"
                                        >
                                    </div>
                                    <div class="col-md-6">
                                        <input 
                                            type="text" 
                                            name="role" 
                                            class="form-control form-control-sm auto-save-field" 
                                            value="{{ $media->metadata['role'] ?? '' }}"
                                            placeholder="Role (e.g., notes)"
                                        >
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Visibility for supplemental --}}
                        <div class="supplemental-fields {{ !$isSupplemental ? 'd-none' : '' }}">
                            <select name="visibility" class="form-select form-select-sm auto-save-field">
                                <option value="public" {{ ($media->metadata['visibility'] ?? 'public') === 'public' ? 'selected' : '' }}>Public</option>
                                <option value="authenticated" {{ ($media->metadata['visibility'] ?? 'public') === 'authenticated' ? 'selected' : '' }}>Authenticated Only</option>
                                <option value="hidden" {{ ($media->metadata['visibility'] ?? 'public') === 'hidden' ? 'selected' : '' }}>Hidden</option>
                            </select>
                        </div>
                    </form>
                </div>

                {{-- Actions --}}
                <div class="col-auto">
                    <div class="d-flex flex-column gap-1">
                        <a href="{{ Storage::url($media->path) }}" 
                           target="_blank" 
                           class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-box-arrow-up-right"></i> View
                        </a>
                        
                        {{-- @if(!$media->is_featured)
                            <form 
                                hx-patch="{{ route('media.update', $media) }}" 
                                hx-target="#media-list" 
                                hx-swap="outerHTML"
                            >
                                @csrf
                                <input type="hidden" name="is_featured" value="1">
                                <button type="submit" class="btn btn-sm btn-outline-secondary w-100">
                                    <i class="bi bi-star"></i> Feature
                                </button>
                            </form>
                        @endif --}}

                        <form 
                            hx-delete="{{ route('media.destroy', $media) }}" 
                            hx-target="#media-list" 
                            hx-swap="outerHTML"
                            hx-confirm="Delete this file?"
                        >
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-danger w-100">
                                <i class="bi bi-trash"></i> Delete
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @empty
            <div class="list-group-item text-muted text-center py-4">
                <i class="bi bi-file-earmark-x"></i> No supplemental files yet. Upload files above to add them.
            </div>
        @endforelse
    </div>

    {{-- Drag & Drop JavaScript --}}
<script>
    // Initialize Sortable for drag-and-drop reordering
    (function() {
        const sortableList = document.getElementById('media-sortable');
        if (sortableList && typeof Sortable !== 'undefined') {
            new Sortable(sortableList, {
                animation: 150,
                handle: '.bi-grip-vertical',
                ghostClass: 'opacity-50',
                onEnd: function(evt) {
                    // Get new order
                    const mediaIds = Array.from(sortableList.children)
                        .filter(el => el.dataset.mediaId) // Filter out empty state
                        .map(el => el.dataset.mediaId);
                    
                    if (mediaIds.length === 0) return;
                    
                    // Send to server
                    fetch('{{ route("items.media.reorder", $item) }}', {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({ order: mediaIds })
                    }).then(response => {
                        if (response.ok) {
                            console.log('Media order updated');
                        }
                    });
                }
            });
        }

        // Toggle supplemental fields visibility
        document.querySelectorAll('.media-type-select').forEach(select => {
            select.addEventListener('change', function() {
                const form = this.closest('.media-edit-form');
                const supplementalFields = form.querySelectorAll('.supplemental-fields');
                
                if (this.value === 'supplemental') {
                    supplementalFields.forEach(field => field.classList.remove('d-none'));
                } else {
                    supplementalFields.forEach(field => field.classList.add('d-none'));
                }
            });
        });

        // Handle successful save - reload media list to show updated badges
        document.body.addEventListener('htmx:afterOnLoad', function(evt) {
            const form = evt.detail.elt;
            if (form.classList.contains('media-edit-form')) {
                // Trigger a refresh of the media list after successful save
                const mediaList = document.getElementById('media-list');
                if (mediaList && mediaList.getAttribute('hx-get')) {
                    setTimeout(() => {
                        htmx.ajax('GET', mediaList.getAttribute('hx-get'), {target: '#media-list', swap: 'outerHTML'});
                    }, 500);
                }
            }
        });
    })();
    </script>
</div>
