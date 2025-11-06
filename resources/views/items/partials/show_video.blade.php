{{-- Video Item Display --}}
@php
    $videoFiles = $item->media->where('is_transcript', false)->filter(fn($m) => str_starts_with($m->mime_type, 'video/'));
@endphp

@if($videoFiles->isNotEmpty())
    <div class="card border-primary mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">
                <i class="bi bi-play-circle"></i> Video Files
            </h5>
        </div>
        <div class="card-body">
            @foreach($videoFiles as $video)
                <div class="video-player-wrapper mb-4 @if(!$loop->last) pb-4 border-bottom @endif">
                    <h6>{{ $video->filename }}</h6>
                    <video controls class="w-100 mb-2" style="max-height: 500px; background: #000;">
                        <source src="{{ Storage::url($video->path) }}" type="{{ $video->mime_type }}">
                        Your browser does not support the video tag.
                    </video>
                    <small class="text-muted">
                        {{ $video->mime_type }} • {{ number_format($video->size / 1024 / 1024, 1) }} MB
                        @if($video->alt_text) • {{ $video->alt_text }} @endif
                    </small>
                </div>
            @endforeach
        </div>
    </div>
@else
    <div class="alert alert-warning">
        <i class="bi bi-exclamation-triangle"></i> No video files attached yet. 
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
