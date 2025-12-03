{{-- Basic Information --}}
<div class="card mb-4">
    <div class="card-header bg-primary bg-opacity-10">
        <h6 class="mb-0">📝 Basic Information</h6>
    </div>
    <div class="card-body">
        <div class="mb-3">
            <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
            <input 
                type="text" 
                class="form-control @error('title') is-invalid @enderror" 
                id="title" 
                name="title" 
                value="{{ old('title', $item->title ?? '') }}" 
                required
            >
            @error('title')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label for="slug" class="form-label">URL Slug</label>
            <input 
                type="text" 
                class="form-control @error('slug') is-invalid @enderror" 
                id="slug" 
                name="slug" 
                value="{{ old('slug', $item->slug ?? '') }}"
            >
            <div class="form-text">Leave blank to auto-generate from title.</div>
            @error('slug')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label for="description" class="form-label">Description</label>
            <textarea 
                class="form-control @error('description') is-invalid @enderror" 
                id="description" 
                name="description" 
                rows="4"
            >{{ old('description', $item->description ?? '') }}</textarea>
            <div class="form-text">Brief summary or abstract of this item.</div>
            @error('description')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="collection_id" class="form-label">Collection</label>
                <select 
                    class="form-select @error('collection_id') is-invalid @enderror" 
                    id="collection_id" 
                    name="collection_id"
                >
                    <option value="">None</option>
                    @foreach($collections as $collection)
                        <option value="{{ $collection->id }}" 
                            {{ old('collection_id', $item->collection_id ?? request('collection_id')) == $collection->id ? 'selected' : '' }}
                        >
                            {{ $collection->title }}
                        </option>
                    @endforeach
                </select>
                <div class="form-text">Optionally group this item in a collection.</div>
                @error('collection_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-6 mb-3">
                <label for="item_type" class="form-label">Item Type <span class="text-danger">*</span></label>
                <select 
                    class="form-select @error('item_type') is-invalid @enderror" 
                    id="item_type" 
                    name="item_type"
                    hx-get="/items/transcript-field"
                    hx-target="#transcript-container"
                    hx-swap="innerHTML"
                    hx-trigger="change"
                    hx-include="[name='item_type']"
                    required
                >
                    <option value="audio" {{ old('item_type', $item->item_type ?? '') == 'audio' ? 'selected' : '' }}>
                        🎵 Audio
                    </option>
                    <option value="video" {{ old('item_type', $item->item_type ?? '') == 'video' ? 'selected' : '' }}>
                        🎬 Video
                    </option>
                    <option value="image" {{ old('item_type', $item->item_type ?? '') == 'image' ? 'selected' : '' }}>
                        🖼️ Image
                    </option>
                    <option value="document" {{ old('item_type', $item->item_type ?? '') == 'document' ? 'selected' : '' }}>
                        📄 Document
                    </option>
                    <option value="other" {{ old('item_type', $item->item_type ?? 'other') == 'other' ? 'selected' : '' }}>
                        📦 Other
                    </option>
                </select>
                <div class="form-text">Primary type determines display format.</div>
                @error('item_type')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>
</div>

{{-- Media & Transcript --}}
<div class="card mb-4">
    <div class="card-header bg-info bg-opacity-10">
        <h6 class="mb-0">🎬 Media & Transcript</h6>
    </div>
    <div class="card-body">
        <div id="transcript-container">
            @include('items._transcript_upload', ['itemType' => old('item_type', $item->item_type ?? 'other')])
        </div>
        <p class="text-muted small mb-0 mt-2">
            <i class="bi bi-info-circle"></i> Additional media files can be uploaded after creating the item.
        </p>
    </div>
</div>

{{-- Dublin Core Metadata --}}
<div class="card mb-4">
    <div class="card-header bg-secondary bg-opacity-10">
        <h6 class="mb-0">📋 Dublin Core Metadata</h6>
    </div>
    <div class="card-body">
        <p class="small text-muted mb-3">
            Standard archival metadata fields. Title and description are auto-filled from above.
        </p>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="dc_creator" class="form-label">Creator</label>
                <input 
                    type="text" 
                    class="form-control @error('dc_creator') is-invalid @enderror" 
                    id="dc_creator" 
                    name="dc_creator" 
                    value="{{ old('dc_creator', isset($item) ? $item->getDC('dc.creator') : '') }}"
                    placeholder="e.g., Jane Smith"
                >
                <div class="form-text">Person or organization who created this.</div>
                @error('dc_creator')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-6 mb-3">
                <label for="dc_date" class="form-label">Date</label>
                <input 
                    type="date" 
                    class="form-control @error('dc_date') is-invalid @enderror" 
                    id="dc_date" 
                    name="dc_date" 
                    value="{{ old('dc_date', isset($item) ? $item->getDC('dc.date') : '') }}"
                >
                <div class="form-text">Creation or event date (YYYY-MM-DD).</div>
                @error('dc_date')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="dc_subject" class="form-label">Subject / Keywords</label>
                <input 
                    type="text" 
                    class="form-control @error('dc_subject') is-invalid @enderror" 
                    id="dc_subject" 
                    name="dc_subject" 
                    value="{{ old('dc_subject', isset($item) ? $item->getDC('dc.subject') : '') }}"
                    placeholder="e.g., Immigration, 1920s"
                >
                <div class="form-text">Comma-separated topics or keywords.</div>
                @error('dc_subject')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-6 mb-3">
                <label for="dc_language" class="form-label">Language</label>
                <input 
                    type="text" 
                    class="form-control @error('dc_language') is-invalid @enderror" 
                    id="dc_language" 
                    name="dc_language" 
                    value="{{ old('dc_language', isset($item) ? $item->getDC('dc.language') : '') }}"
                    placeholder="e.g., en, es, fr"
                    maxlength="10"
                >
                <div class="form-text">ISO language code (e.g., en for English).</div>
                @error('dc_language')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="mb-0">
            <label for="dc_rights" class="form-label">Rights Statement</label>
            <textarea 
                class="form-control @error('dc_rights') is-invalid @enderror" 
                id="dc_rights" 
                name="dc_rights" 
                rows="2"
                placeholder="e.g., CC-BY-NC 4.0, Public Domain"
            >{{ old('dc_rights', isset($item) ? $item->getDC('dc.rights') : '') }}</textarea>
            <div class="form-text">Copyright or licensing information.</div>
            @error('dc_rights')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>

{{-- Taxonomies / Tags --}}
<div class="card mb-4">
    <div class="card-header bg-warning bg-opacity-10">
        <h6 class="mb-0">🏷️ Tags & Categories</h6>
    </div>
    <div class="card-body">
        @include('partials._taxonomy_selector', ['resource' => $item ?? null])
    </div>
</div>

{{-- Workflow & Visibility --}}
<div class="card mb-4">
    <div class="card-header bg-success bg-opacity-10">
        <h6 class="mb-0">⚙️ Workflow & Visibility</h6>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="status" class="form-label">
                    Workflow Status <span class="text-danger">*</span>
                </label>
                <select 
                    class="form-select @error('status') is-invalid @enderror" 
                    id="status" 
                    name="status" 
                    required
                >
                    @if(Auth::user()->isContributor() && !Auth::user()->isCurator())
                        {{-- Contributors can only use draft and in_review --}}
                        <option value="draft" {{ old('status', $item->status ?? 'draft') == 'draft' ? 'selected' : '' }}>
                            Draft
                        </option>
                        <option value="in_review" {{ old('status', $item->status ?? 'draft') == 'in_review' ? 'selected' : '' }}>
                            In Review
                        </option>
                    @else
                        {{-- Curators and admins have full access --}}
                        <option value="draft" {{ old('status', $item->status ?? 'draft') == 'draft' ? 'selected' : '' }}>
                            Draft
                        </option>
                        <option value="in_review" {{ old('status', $item->status ?? 'draft') == 'in_review' ? 'selected' : '' }}>
                            In Review
                        </option>
                        <option value="published" {{ old('status', $item->status ?? 'draft') == 'published' ? 'selected' : '' }}>
                            Published
                        </option>
                        <option value="archived" {{ old('status', $item->status ?? 'draft') == 'archived' ? 'selected' : '' }}>
                            Archived
                        </option>
                    @endif
                </select>
                <div class="form-text">
                    <strong>Draft:</strong> Work in progress<br>
                    <strong>In Review:</strong> Ready for curator approval<br>
                    @if(Auth::user()->isCurator())
                        <strong>Published:</strong> Live and visible per visibility setting<br>
                        <strong>Archived:</strong> Hidden from public listings
                    @endif
                </div>
                @error('status')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-6 mb-3">
                <label for="visibility" class="form-label">
                    Visibility <span class="text-danger">*</span>
                </label>
                <select 
                    class="form-select @error('visibility') is-invalid @enderror" 
                    id="visibility" 
                    name="visibility" 
                    required
                >
                    <option value="public" {{ old('visibility', $item->visibility ?? 'authenticated') == 'public' ? 'selected' : '' }}>
                        Public
                    </option>
                    <option value="authenticated" {{ old('visibility', $item->visibility ?? 'authenticated') == 'authenticated' ? 'selected' : '' }}>
                        Authenticated
                    </option>
                    <option value="hidden" {{ old('visibility', $item->visibility ?? 'authenticated') == 'hidden' ? 'selected' : '' }}>
                        Hidden
                    </option>
                </select>
                <div class="form-text">
                    <strong>Public:</strong> Anyone can view<br>
                    <strong>Authenticated:</strong> Login required<br>
                    <strong>Hidden:</strong> Admins and curators only
                </div>
                @error('visibility')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="alert alert-info small mb-0">
            <i class="bi bi-info-circle"></i>
            <strong>Note:</strong> Items must be set to "Published" status <em>and</em> have appropriate visibility to appear on the public site.
        </div>
    </div>
</div>

