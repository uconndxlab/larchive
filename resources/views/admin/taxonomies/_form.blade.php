<div class="mb-3">
    <label for="key" class="form-label">Key <span class="text-danger">*</span></label>
    <input 
        type="text" 
        class="form-control @error('key') is-invalid @enderror" 
        id="key" 
        name="key" 
        value="{{ old('key', $taxonomy->key ?? '') }}" 
        required
        pattern="[a-z0-9_-]+"
    >
    <div class="form-text">
        Lowercase alphanumeric characters, dashes, and underscores only. Used in URLs and code.
    </div>
    @error('key')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
    <input 
        type="text" 
        class="form-control @error('name') is-invalid @enderror" 
        id="name" 
        name="name" 
        value="{{ old('name', $taxonomy->name ?? '') }}" 
        required
    >
    <div class="form-text">Human-readable name for this taxonomy.</div>
    @error('name')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="description" class="form-label">Description</label>
    <textarea 
        class="form-control @error('description') is-invalid @enderror" 
        id="description" 
        name="description" 
        rows="3"
    >{{ old('description', $taxonomy->description ?? '') }}</textarea>
    <div class="form-text">Optional description of what this taxonomy is for.</div>
    @error('description')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <div class="form-check">
        <input 
            type="checkbox" 
            class="form-check-input" 
            id="hierarchical" 
            name="hierarchical" 
            value="1"
            {{ old('hierarchical', isset($taxonomy) && $taxonomy->hierarchical) ? 'checked' : '' }}
        >
        <label class="form-check-label" for="hierarchical">
            Hierarchical
        </label>
    </div>
    <div class="form-text">
        Enable parent/child relationships between terms (like categories). 
        Leave unchecked for simple tags.
    </div>
</div>
