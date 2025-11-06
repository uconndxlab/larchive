<div class="mb-3">
    <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
    <input 
        type="text" 
        class="form-control @error('title') is-invalid @enderror" 
        id="title" 
        name="title" 
        value="{{ old('title', $collection->title ?? '') }}" 
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
        value="{{ old('slug', $collection->slug ?? '') }}"
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
        rows="5"
    >{{ old('description', $collection->description ?? '') }}</textarea>
    @error('description')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <div class="form-check">
        <input 
            type="checkbox" 
            class="form-check-input" 
            id="publish_now" 
            name="publish_now" 
            value="1"
            {{ old('publish_now', isset($collection) && $collection->published_at) ? 'checked' : '' }}
        >
        <label class="form-check-label" for="publish_now">
            Publish now
        </label>
    </div>
    <div class="form-text">Check to make this collection publicly visible.</div>
</div>
