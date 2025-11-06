{{-- Image Item Display --}}
@php
    $imageFiles = $item->media->where('is_transcript', false)->filter(fn($m) => str_starts_with($m->mime_type, 'image/'));
@endphp

@if($imageFiles->isNotEmpty())
    <div class="card border-success mb-4">
        <div class="card-header bg-success bg-opacity-10">
            <h5 class="mb-0">
                <i class="bi bi-image"></i> Images
            </h5>
        </div>
        <div class="card-body">
            @foreach($imageFiles as $image)
                <div class="image-wrapper mb-4 @if(!$loop->last) pb-4 border-bottom @endif">
                    <figure class="figure w-100">
                        <img src="{{ Storage::url($image->path) }}" 
                             alt="{{ $image->alt_text }}" 
                             class="figure-img img-fluid rounded shadow-sm"
                             style="max-height: 700px; width: auto; display: block; margin: 0 auto;">
                        
                        @if($image->filename || $image->alt_text)
                            <figcaption class="figure-caption text-center mt-2">
                                <strong>{{ $image->filename }}</strong>
                                @if($image->alt_text)
                                    <br>{{ $image->alt_text }}
                                @endif
                                <br>
                                <small class="text-muted">
                                    {{ $image->mime_type }}
                                    @if(isset($image->meta['width']) && isset($image->meta['height']))
                                        • {{ $image->meta['width'] }} × {{ $image->meta['height'] }} px
                                    @endif
                                    • {{ number_format($image->size / 1024, 1) }} KB
                                </small>
                            </figcaption>
                        @endif
                    </figure>
                </div>
            @endforeach
        </div>
    </div>
@else
    <div class="alert alert-warning">
        <i class="bi bi-exclamation-triangle"></i> No images attached yet. 
        <a href="{{ route('items.edit', $item) }}">Add media files</a> to display images.
    </div>
@endif
