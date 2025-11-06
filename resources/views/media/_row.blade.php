<tr data-media-id="{{ $media->id }}">
    <td>
        @if(str_starts_with($media->mime_type, 'image/'))
            <img src="{{ Storage::url($media->path) }}" 
                 alt="{{ $media->alt_text }}" 
                 class="img-thumbnail" 
                 style="max-width: 60px; max-height: 60px; object-fit: cover;">
        @else
            <div class="text-center p-2 bg-light rounded" style="width: 60px; height: 60px; display: flex; align-items: center; justify-content: center;">
                <strong class="text-muted small">
                    {{ strtoupper(pathinfo($media->filename, PATHINFO_EXTENSION)) }}
                </strong>
            </div>
        @endif
    </td>
    <td>
        <small class="text-muted d-block">{{ $media->mime_type }}</small>
        {{ $media->filename }}
    </td>
    <td>
        <span class="badge bg-secondary">
            {{ number_format($media->size / 1024, 1) }} KB
        </span>
    </td>
    <td>
        <form hx-patch="/media/{{ $media->id }}" 
              hx-target="closest tr" 
              hx-swap="outerHTML"
              class="d-flex gap-1">
            @csrf
            <input type="text" 
                   name="alt_text" 
                   value="{{ $media->alt_text }}" 
                   class="form-control form-control-sm" 
                   placeholder="Add alt text...">
            <button type="submit" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-check"></i>
            </button>
        </form>
    </td>
    <td class="text-center">
        <div class="btn-group btn-group-sm" role="group">
            <button type="button" 
                    class="btn btn-outline-secondary btn-sm move-up">
                <i class="bi bi-arrow-up"></i>
            </button>
            <button type="button" 
                    class="btn btn-outline-secondary btn-sm move-down">
                <i class="bi bi-arrow-down"></i>
            </button>
        </div>
    </td>
    <td class="text-center">
        <button type="button"
                class="btn btn-sm btn-outline-danger"
                hx-delete="/media/{{ $media->id }}"
                hx-target="#media-list"
                hx-swap="outerHTML"
                hx-confirm="Delete this media file?">
            <i class="bi bi-trash"></i>
        </button>
    </td>
</tr>
