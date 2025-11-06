{{-- Other/Generic Item Display --}}
@php
    $mediaFiles = $item->media->where('is_transcript', false);
@endphp

@if($mediaFiles->isNotEmpty())
    <div class="card border-secondary mb-4">
        <div class="card-header bg-secondary bg-opacity-10">
            <h5 class="mb-0">
                <i class="bi bi-file-earmark"></i> Attached Files
            </h5>
        </div>
        <div class="card-body">
            <div class="list-group list-group-flush">
                @foreach($mediaFiles as $media)
                    <div class="list-group-item">
                        <div class="d-flex align-items-center">
                            <div class="me-3">
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
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="mb-1">{{ $media->filename }}</h6>
                                <small class="text-muted">
                                    {{ $media->mime_type }} • {{ number_format($media->size / 1024, 1) }} KB
                                    @if($media->alt_text) • {{ $media->alt_text }} @endif
                                </small>
                            </div>
                            <div>
                                <a href="{{ Storage::url($media->path) }}" 
                                   class="btn btn-sm btn-outline-primary" 
                                   download>
                                    <i class="bi bi-download"></i> Download
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@else
    <div class="alert alert-info">
        <i class="bi bi-info-circle"></i> No files attached yet. 
        <a href="{{ route('items.edit', $item) }}">Add media files</a> to this item.
    </div>
@endif

{{-- Dublin Core Metadata --}}
@if($item->metadata->isNotEmpty())
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="bi bi-tags"></i> Metadata
            </h5>
        </div>
        <div class="card-body">
            <dl class="row mb-0">
                @foreach($item->getDublinCore() as $key => $value)
                    <dt class="col-sm-4 text-muted small">{{ \App\Models\Concerns\DublinCore::getLabel($key) }}</dt>
                    <dd class="col-sm-8">{{ $value }}</dd>
                @endforeach
            </dl>
        </div>
    </div>
@endif
