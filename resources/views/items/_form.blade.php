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
    <label for="slug" class="form-label">Slug</label>
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
    <div class="form-text">Optionally assign this item to a collection.</div>
    @error('collection_id')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
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
            🎵 Audio (oral history, interview, music)
        </option>
        <option value="video" {{ old('item_type', $item->item_type ?? '') == 'video' ? 'selected' : '' }}>
            🎬 Video (documentary, interview, film)
        </option>
        <option value="image" {{ old('item_type', $item->item_type ?? '') == 'image' ? 'selected' : '' }}>
            🖼️ Image (photograph, scan, artwork)
        </option>
        <option value="document" {{ old('item_type', $item->item_type ?? '') == 'document' ? 'selected' : '' }}>
            📄 Document (letter, manuscript, report)
        </option>
        <option value="other" {{ old('item_type', $item->item_type ?? 'other') == 'other' ? 'selected' : '' }}>
            📦 Other
        </option>
    </select>
    <div class="form-text">Primary type determines how media is displayed.</div>
    @error('item_type')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

{{-- Transcript upload container - dynamically loaded via HTMX --}}
<div id="transcript-container">
    @include('items._transcript_upload', ['itemType' => old('item_type', $item->item_type ?? 'other')])
</div>

<div class="mb-3">
    <label for="description" class="form-label">Description</label>
    <textarea 
        class="form-control @error('description') is-invalid @enderror" 
        id="description" 
        name="description" 
        rows="5"
    >{{ old('description', $item->description ?? '') }}</textarea>
    @error('description')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

{{-- Dublin Core Metadata Section --}}
<div class="card border-secondary mb-3">
    <div class="card-header bg-secondary bg-opacity-10">
        <h6 class="mb-0">
            Dublin Core Metadata (Optional)
        </h6>
    </div>
    <div class="card-body">
        <p class="small text-muted mb-3">
            These fields follow the Dublin Core metadata standard for archival items.
            <strong>dc.title</strong> and <strong>dc.description</strong> are auto-filled from Title and Description above.
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
                    placeholder="e.g., Interviewer: Jane Smith"
                >
                <small class="form-text text-muted">Person or organization responsible for creating this item</small>
                @error('dc_creator')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-6 mb-3">
                <label for="dc_date" class="form-label">Date (YYYY-MM-DD)</label>
                <input 
                    type="date" 
                    class="form-control @error('dc_date') is-invalid @enderror" 
                    id="dc_date" 
                    name="dc_date" 
                    value="{{ old('dc_date', isset($item) ? $item->getDC('dc.date') : '') }}"
                >
                <small class="form-text text-muted">Date associated with the creation or event</small>
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
                    placeholder="e.g., World War II, Chicago, Immigration"
                >
                <small class="form-text text-muted">Topics or keywords (comma-separated)</small>
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
                <small class="form-text text-muted">ISO 639 language code</small>
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
                placeholder="e.g., CC-BY-NC 4.0, In Copyright, Public Domain"
            >{{ old('dc_rights', isset($item) ? $item->getDC('dc.rights') : '') }}</textarea>
            <small class="form-text text-muted">License or rights information</small>
            @error('dc_rights')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>

<div class="mb-3">
    <div class="form-check">
        <input 
            type="checkbox" 
            class="form-check-input" 
            id="publish_now" 
            name="publish_now" 
            value="1"
            {{ old('publish_now', isset($item) && $item->published_at) ? 'checked' : '' }}
        >
        <label class="form-check-label" for="publish_now">
            Publish now
        </label>
    </div>
    <div class="form-text">Check to make this item publicly visible.</div>
</div>
