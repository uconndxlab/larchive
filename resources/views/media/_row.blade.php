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
        
        {{-- Processing Status Badge --}}
        @if($media->processing_status !== 'ready')
            <div class="mt-1">
                @if($media->processing_status === 'uploading')
                    <span class="badge bg-info">
                        <i class="bi bi-cloud-upload"></i> Uploading...
                    </span>
                @elseif($media->processing_status === 'uploaded')
                    <span class="badge bg-primary">
                        <i class="bi bi-clock"></i> Queued
                    </span>
                @elseif($media->processing_status === 'processing')
                    <span class="badge bg-warning">
                        <i class="bi bi-gear-fill"></i> Processing...
                    </span>
                @elseif($media->processing_status === 'failed')
                    <span class="badge bg-danger" 
                          title="{{ $media->processing_error }}"
                          data-bs-toggle="tooltip">
                        <i class="bi bi-exclamation-triangle"></i> Failed
                    </span>
                @endif
            </div>
        @endif
        
        {{-- Metadata Display (if ready) --}}
        @if($media->isReady() && $media->metadata)
            <div class="mt-1">
                <small class="text-muted">
                    @if(isset($media->metadata['duration']))
                        <i class="bi bi-stopwatch"></i> {{ $media->formattedDuration }}
                    @endif
                    @if(isset($media->metadata['width']) && isset($media->metadata['height']))
                        <i class="bi bi-arrows-angle-expand"></i> {{ $media->metadata['width'] }}×{{ $media->metadata['height'] }}
                    @endif
                    @if(isset($media->metadata['size_human']))
                        {{ $media->metadata['size_human'] }}
                    @endif
                </small>
            </div>
        @endif
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
