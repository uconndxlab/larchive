<div class="row">
    <div class="col-md-8">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-light">
                <h5 class="mb-0">Basic Information</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
                    <input type="text" 
                           class="form-control @error('title') is-invalid @enderror" 
                           id="title" 
                           name="title" 
                           value="{{ old('title', $exhibit->title ?? '') }}" 
                           required>
                    @error('title')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="slug" class="form-label">Slug</label>
                    <input type="text" 
                           class="form-control @error('slug') is-invalid @enderror" 
                           id="slug" 
                           name="slug" 
                           value="{{ old('slug', $exhibit->slug ?? '') }}"
                           placeholder="auto-generated-from-title">
                    <small class="text-muted">Leave blank to auto-generate from title</small>
                    @error('slug')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control @error('description') is-invalid @enderror" 
                              id="description" 
                              name="description" 
                              rows="4">{{ old('description', $exhibit->description ?? '') }}</textarea>
                    @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="credits" class="form-label">Credits</label>
                    <textarea class="form-control @error('credits') is-invalid @enderror" 
                              id="credits" 
                              name="credits" 
                              rows="2"
                              placeholder="Curator, contributors, etc.">{{ old('credits', $exhibit->credits ?? '') }}</textarea>
                    @error('credits')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="cover_image" class="form-label">Cover Image</label>
                    
                    @if(isset($exhibit) && $exhibit->cover_image)
                        <div class="mb-2">
                            <img src="{{ Storage::url($exhibit->cover_image) }}" 
                                 class="img-thumbnail" 
                                 alt="Current cover image"
                                 style="max-height: 200px;">
                            <div class="small text-muted mt-1">Current cover image</div>
                        </div>
                    @endif
                    
                    <input type="file" 
                           class="form-control @error('cover_image') is-invalid @enderror" 
                           id="cover_image" 
                           name="cover_image"
                           accept="image/*">
                    <small class="text-muted">Recommended size: 1200x600px. Max 2MB.</small>
                    @error('cover_image')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-light">
                <h5 class="mb-0">Publishing</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" 
                               type="checkbox" 
                               id="published" 
                               name="published"
                               value="1"
                               {{ old('published', isset($exhibit) && $exhibit->isPublished() ? '1' : '0') == '1' ? 'checked' : '' }}>
                        <label class="form-check-label" for="published">
                            Published
                        </label>
                    </div>
                    <small class="text-muted">Make this exhibit visible to the public</small>
                </div>

                <div class="mb-3">
                    <label for="visibility" class="form-label">Visibility <span class="text-danger">*</span></label>
                    <select 
                        class="form-select @error('visibility') is-invalid @enderror" 
                        id="visibility" 
                        name="visibility" 
                        required
                    >
                        <option value="public" {{ old('visibility', $exhibit->visibility ?? 'authenticated') == 'public' ? 'selected' : '' }}>
                            Public - Visible to everyone
                        </option>
                        <option value="authenticated" {{ old('visibility', $exhibit->visibility ?? 'authenticated') == 'authenticated' ? 'selected' : '' }}>
                            Authenticated - Requires login
                        </option>
                        <option value="hidden" {{ old('visibility', $exhibit->visibility ?? 'authenticated') == 'hidden' ? 'selected' : '' }}>
                            Hidden - Admin only
                        </option>
                    </select>
                    <small class="text-muted">Control who can view this exhibit</small>
                    @error('visibility')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" 
                               type="checkbox" 
                               id="featured" 
                               name="featured"
                               value="1"
                               {{ old('featured', $exhibit->featured ?? false) ? 'checked' : '' }}>
                        <label class="form-check-label" for="featured">
                            Featured
                        </label>
                    </div>
                    <small class="text-muted">Highlight on homepage</small>
                </div>

                @if(isset($exhibit) && $exhibit->featured)
                    <div class="mb-3">
                        <label for="sort_order" class="form-label">Sort Order</label>
                        <input type="number" 
                               class="form-control @error('sort_order') is-invalid @enderror" 
                               id="sort_order" 
                               name="sort_order" 
                               value="{{ old('sort_order', $exhibit->sort_order ?? 0) }}"
                               min="0">
                        <small class="text-muted">Lower numbers appear first</small>
                        @error('sort_order')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                @endif
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-light">
                <h5 class="mb-0">Theme</h5>
            </div>
            <div class="card-body">
                <div class="mb-0">
                    <label for="theme" class="form-label">Theme</label>
                    <select class="form-select @error('theme') is-invalid @enderror" 
                            id="theme" 
                            name="theme">
                        <option value="default" {{ old('theme', $exhibit->theme ?? 'default') == 'default' ? 'selected' : '' }}>
                            Default
                        </option>
                        <option value="timeline" {{ old('theme', $exhibit->theme ?? '') == 'timeline' ? 'selected' : '' }}>
                            Timeline
                        </option>
                        <option value="gallery" {{ old('theme', $exhibit->theme ?? '') == 'gallery' ? 'selected' : '' }}>
                            Gallery
                        </option>
                    </select>
                    <small class="text-muted">Display style for this exhibit</small>
                    @error('theme')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>

        @include('partials._taxonomy_selector', ['resource' => $exhibit ?? null])
    </div>
</div>
