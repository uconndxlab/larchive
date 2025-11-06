@forelse($item->media->sortBy('sort_order') as $media)
    <div class="card mb-3" data-media-id="{{ $media->id }}">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-2 text-center">
                    @if(str_starts_with($media->mime_type, 'image/'))
                        <img src="{{ Storage::url($media->path) }}" alt="{{ $media->alt_text }}" class="img-thumbnail" style="max-height: 80px;">
                    @else
                        <div class="text-muted">
                            <i class="bi bi-file-earmark" style="font-size: 2rem;"></i>
                        </div>
                    @endif
                </div>
                <div class="col-md-7">
                    <h6 class="mb-1">{{ $media->filename }}</h6>
                    <small class="text-muted">
                        {{ $media->mime_type }} • {{ number_format($media->size / 1024, 2) }} KB
                        @if($media->meta && isset($media->meta['width'], $media->meta['height']))
                            • {{ $media->meta['width'] }}×{{ $media->meta['height'] }}
                        @endif
                    </small>
                    @if($media->alt_text)
                        <div class="small text-muted mt-1">Alt: {{ $media->alt_text }}</div>
                    @endif
                </div>
                <div class="col-md-3 text-end">
                    <div class="btn-group btn-group-sm">
                        <a href="{{ Storage::url($media->path) }}" target="_blank" class="btn btn-outline-primary">View</a>
                        <form 
                            hx-delete="{{ route('media.destroy', $media) }}" 
                            hx-target="#media-list" 
                            hx-swap="innerHTML"
                            hx-confirm="Delete this file?"
                        >
                            @csrf
                            <button type="submit" class="btn btn-outline-danger">Delete</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@empty
    <p class="text-muted">No media files yet.</p>
@endforelse
