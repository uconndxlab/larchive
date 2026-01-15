{{-- Featured Image --}}
<div class="card my-3">
    <div class="card-header">
        <h6 class="mb-0">Featured Image</h6>
    </div>
    <div class="card-body">
        @if(isset($item) && $item->featuredImage)
            <div class="mb-3">
                <img src="{{ Storage::url($item->featuredImage->path) }}" 
                     alt="{{ $item->featuredImage->alt_text }}" 
                     class="img-thumbnail w-100"
                     style="max-height: 200px; object-fit: cover;">
                <div class="mt-2">
                    <label class="form-check-label small">
                        <input type="checkbox" name="remove_featured_image" value="1" class="form-check-input" form="item-edit-form">
                        Remove current image
                    </label>
                </div>
            </div>
        @endif

        <div class="mb-3">
            <label for="featured_image" class="form-label small">
                {{ isset($item) && $item->featuredImage ? 'Replace Image' : 'Upload Image' }}
            </label>
            <input 
                type="file" 
                class="form-control form-control-sm @error('featured_image') is-invalid @enderror" 
                id="featured_image" 
                name="featured_image"
                accept="image/*"
                form="item-edit-form"
            >
            <div class="form-text small">
                Primary image for this item.
            </div>
            @error('featured_image')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        @if(isset($item) && $item->media()->where('mime_type', 'like', 'image/%')->count() > 0)
            <div class="mb-0">
                <label for="existing_featured_image" class="form-label small">Or select existing:</label>
                <select class="form-select form-select-sm" id="existing_featured_image" name="existing_featured_image_id" form="item-edit-form">
                    <option value="">-- Choose --</option>
                    @foreach($item->media()->where('mime_type', 'like', 'image/%')->get() as $media)
                        <option value="{{ $media->id }}" {{ $item->featured_image_id == $media->id ? 'selected' : '' }}>
                            {{ Str::limit($media->filename, 25) }}
                        </option>
                    @endforeach
                </select>
            </div>
        @endif
    </div>
</div>
