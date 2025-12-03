<div class="mb-3">
    <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
    <input 
        type="text" 
        class="form-control @error('name') is-invalid @enderror" 
        id="name" 
        name="name" 
        value="{{ old('name', $term->name ?? '') }}" 
        required
    >
    @error('name')
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
        value="{{ old('slug', $term->slug ?? '') }}"
        pattern="[a-z0-9_-]+"
    >
    <div class="form-text">Leave blank to auto-generate from name. Lowercase alphanumeric, dashes, and underscores only.</div>
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
        rows="3"
    >{{ old('description', $term->description ?? '') }}</textarea>
    @error('description')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

@if($taxonomy->hierarchical && $parentTerms->isNotEmpty())
    <div class="mb-3">
        <label for="parent_id" class="form-label">Parent Term</label>
        <select 
            class="form-select @error('parent_id') is-invalid @enderror" 
            id="parent_id" 
            name="parent_id"
        >
            <option value="">None (Top Level)</option>
            @foreach($parentTerms as $parentTerm)
                <option value="{{ $parentTerm->id }}" {{ old('parent_id', $term->parent_id ?? '') == $parentTerm->id ? 'selected' : '' }}>
                    {{ $parentTerm->name }}
                </option>
            @endforeach
        </select>
        <div class="form-text">Optional parent term for hierarchical organization.</div>
        @error('parent_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
@endif
