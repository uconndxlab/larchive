{{-- Audio Item Display --}}
@php
    $audioFiles = $item->media->where('is_transcript', false)->filter(fn($m) => str_starts_with($m->mime_type, 'audio/'));
@endphp

@if($audioFiles->isNotEmpty())
    @include('partials.audio-player', ['audioFiles' => $audioFiles])
@else
    <div class="alert alert-warning">
        <i class="bi bi-exclamation-triangle"></i> No audio files attached yet. 
        <a href="{{ route('items.edit', $item) }}">Add media files</a> to enable playback.
    </div>
@endif

{{-- Transcript Download --}}
@if($item->hasTranscript())
    <div class="card border-info mb-4">
        <div class="card-header bg-info bg-opacity-10">
            <h5 class="mb-0">
                <i class="bi bi-file-text"></i> Transcript
            </h5>
        </div>
        <div class="card-body">
            <div class="d-flex align-items-center">
                <div class="me-3">
                    <i class="bi bi-file-earmark-text fs-1 text-info"></i>
                </div>
                <div class="flex-grow-1">
                    <h6 class="mb-1">{{ $item->transcript->filename }}</h6>
                    <small class="text-muted">
                        {{ $item->transcript->mime_type }} • 
                        {{ number_format($item->transcript->size / 1024, 1) }} KB
                    </small>
                </div>
                <div>
                    <a href="{{ Storage::url($item->transcript->path) }}" 
                       class="btn btn-primary" 
                       download
                       target="_blank">
                        <i class="bi bi-download"></i> Download
                    </a>
                </div>
            </div>
        </div>
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
