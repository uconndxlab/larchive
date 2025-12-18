{{-- Basic Details --}}
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">Basic Details</h5>
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
            <label for="description" class="form-label">Short Description</label>
            <textarea 
                class="form-control @error('description') is-invalid @enderror" 
                id="description" 
                name="description" 
                rows="3"
            >{{ old('description', $item->description ?? '') }}</textarea>
            <div class="form-text">Brief summary or abstract of this item.</div>
            @error('description')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="row">
            <div class="col-md-4 mb-3">
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
                    <option value="audio" {{ old('item_type', $item->item_type ?? '') == 'audio' ? 'selected' : '' }}>Audio</option>
                    <option value="video" {{ old('item_type', $item->item_type ?? '') == 'video' ? 'selected' : '' }}>Video</option>
                    <option value="image" {{ old('item_type', $item->item_type ?? '') == 'image' ? 'selected' : '' }}>Image</option>
                    <option value="document" {{ old('item_type', $item->item_type ?? '') == 'document' ? 'selected' : '' }}>Document</option>
                    <option value="other" {{ old('item_type', $item->item_type ?? 'other') == 'other' ? 'selected' : '' }}>Other</option>
                </select>
                <div class="form-text">Primary type determines display format.</div>
                @error('item_type')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-4 mb-3">
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

            <div class="col-md-4 mb-3">
                <label for="dc_date" class="form-label">Date</label>
                <input 
                    type="date" 
                    class="form-control @error('dc_date') is-invalid @enderror" 
                    id="dc_date" 
                    name="dc_date" 
                    value="{{ old('dc_date', isset($item) ? $item->getDC('dc.date') : '') }}"
                >
                <div class="form-text">Creation or event date.</div>
                @error('dc_date')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="mb-0">
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
    </div>
</div>

{{-- Additional Metadata --}}
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">Additional Metadata</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="dc_creator" class="form-label">Creator</label>
                <input 
                    type="text" 
                    class="form-control @error('dc_creator') is-invalid @enderror" 
                    id="dc_creator" 
                    name="dc_creator" 
                    value="{{ old('dc_creator', isset($item) ? $item->getDC('dc.creator') : '') }}"
                    placeholder="Person or organization"
                >
                <div class="form-text">Person or organization who created this item.</div>
                @error('dc_creator')
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

        <div class="mb-3">
            <label for="dc_subject" class="form-label">Subject / Keywords</label>
            <input 
                type="text" 
                class="form-control @error('dc_subject') is-invalid @enderror" 
                id="dc_subject" 
                name="dc_subject" 
                value="{{ old('dc_subject', isset($item) ? $item->getDC('dc.subject') : '') }}"
                placeholder="e.g., Immigration, 1920s"
            >
            <div class="form-text">Comma-separated topics or keywords for this specific item.</div>
            @error('dc_subject')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
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



