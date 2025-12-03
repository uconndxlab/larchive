@extends('layouts.app')

@section('content')
{{-- Sing Sing Themed Item Display --}}
<div class="sing-sing-item-header mb-4">
    <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
            <h1 class="display-6 mb-2">{{ $item->title }}</h1>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <span class="badge sing-sing-badge">
                    @switch($item->item_type)
                        @case('audio') <i class="bi bi-music-note"></i> Audio @break
                        @case('video') <i class="bi bi-camera-video"></i> Video @break
                        @case('image') <i class="bi bi-image"></i> Image @break
                        @case('document') <i class="bi bi-file-earmark-text"></i> Document @break
                        @default <i class="bi bi-file-earmark"></i> {{ ucfirst($item->item_type) }}
                    @endswitch
                </span>
                @if($item->published_at)
                    <span class="badge bg-success">
                        <i class="bi bi-check-circle"></i> Published
                    </span>
                @else
                    <span class="badge bg-secondary">
                        <i class="bi bi-clock"></i> Draft
                    </span>
                @endif
                @if($item->collection)
                    <a href="{{ route('collections.show', $item->collection) }}" class="badge bg-light text-dark text-decoration-none border">
                        <i class="bi bi-folder"></i> {{ $item->collection->title }}
                    </a>
                @endif
            </div>
        </div>
        <div class="btn-group">
            <a href="{{ route('items.edit', $item) }}" class="btn btn-primary">
                <i class="bi bi-pencil"></i> Edit
            </a>
            <a href="{{ route('items.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back
            </a>
        </div>
    </div>

    @if($item->terms->isNotEmpty())
        <div class="mb-3">
            @php
                $termsByTaxonomy = $item->terms->groupBy('taxonomy.name');
            @endphp
            
            @foreach($termsByTaxonomy as $taxonomyName => $terms)
                <div class="mb-2">
                    <strong class="small text-muted">{{ $taxonomyName }}:</strong>
                    @foreach($terms as $term)
                        <a href="{{ route('terms.show', [$term->taxonomy, $term]) }}" 
                           class="badge sing-sing-badge-secondary text-decoration-none me-1">
                            {{ $term->name }}
                        </a>
                    @endforeach
                </div>
            @endforeach
        </div>
    @endif

    @if($item->description)
        <div class="alert alert-light border sing-sing-description">
            <p class="mb-0">{{ $item->description }}</p>
        </div>
    @endif
</div>

<div class="row g-4">
    <div class="col-12">
        {{-- Type-specific content rendering --}}
        @switch($item->item_type)
            @case('audio')
                @include('items.partials.show_audio')
                @break
            @case('video')
                @include('items.partials.show_video')
                @break
            @case('image')
                @include('items.partials.show_image')
                @break
            @case('document')
                @include('items.partials.show_document')
                @break
            @default
                @include('items.partials.show_other')
        @endswitch

        {{-- OHMS Viewer --}}
        @if(!empty($item->ohms_json))
            @include('items.partials.show_ohms')
        @endif

        {{-- Metadata & Details Card with Sing Sing Styling --}}
        <div class="card shadow-sm sing-sing-card">
            <div class="card-header sing-sing-card-header">
                <h5 class="mb-0">
                    <i class="bi bi-info-circle"></i>
                    Archival Details
                </h5>
            </div>
            <div class="card-body">
                <div class="row g-4">
                    <div class="col-md-6">
                        <h6 class="text-muted text-uppercase small mb-3">Basic Information</h6>
                        <dl class="row mb-0">
                            <dt class="col-sm-4 small text-muted">Identifier</dt>
                            <dd class="col-sm-8"><code class="text-primary">{{ $item->slug }}</code></dd>

                            <dt class="col-sm-4 small text-muted">Type</dt>
                            <dd class="col-sm-8">{{ ucfirst($item->item_type) }}</dd>

                            <dt class="col-sm-4 small text-muted">Collection</dt>
                            <dd class="col-sm-8">
                                @if($item->collection)
                                    <a href="{{ route('collections.show', $item->collection) }}" class="text-decoration-none">
                                        {{ $item->collection->title }}
                                    </a>
                                @else
                                    <span class="text-muted fst-italic">Uncategorized</span>
                                @endif
                            </dd>

                            <dt class="col-sm-4 small text-muted">Status</dt>
                            <dd class="col-sm-8">
                                @if($item->published_at)
                                    <span class="text-success">Published on {{ $item->published_at->format('M d, Y') }}</span>
                                @else
                                    <span class="text-muted">Draft</span>
                                @endif
                            </dd>
                        </dl>
                    </div>

                    <div class="col-md-6">
                        <h6 class="text-muted text-uppercase small mb-3">System Information</h6>
                        <dl class="row mb-0">
                            <dt class="col-sm-4 small text-muted">Created</dt>
                            <dd class="col-sm-8">{{ $item->created_at->format('M d, Y') }}<br><small class="text-muted">{{ $item->created_at->format('g:i A') }}</small></dd>

                            <dt class="col-sm-4 small text-muted">Last Updated</dt>
                            <dd class="col-sm-8">{{ $item->updated_at->format('M d, Y') }}<br><small class="text-muted">{{ $item->updated_at->format('g:i A') }}</small></dd>

                            @if(!empty($item->ohms_json))
                                <dt class="col-sm-4 small text-muted">OHMS Data</dt>
                                <dd class="col-sm-8">
                                    <span class="badge bg-primary">
                                        <i class="bi bi-mic-fill"></i>
                                        {{ count($item->ohms_json['segments'] ?? []) }} segments
                                    </span>
                                </dd>
                            @endif

                            @if($item->hasTranscript())
                                <dt class="col-sm-4 small text-muted">Transcript</dt>
                                <dd class="col-sm-8">
                                    <a href="{{ Storage::url($item->transcript->file_path) }}" target="_blank" class="text-decoration-none">
                                        <i class="bi bi-file-text"></i>
                                        {{ $item->transcript->original_filename }}
                                    </a>
                                </dd>
                            @endif
                        </dl>
                    </div>
                </div>

                {{-- Dublin Core Metadata --}}
                @php
                    $dcMetadata = $item->getDublinCore();
                @endphp
                @if(!empty($dcMetadata))
                    <hr class="my-4">
                    <h6 class="text-muted text-uppercase small mb-3">
                        <i class="bi bi-tags"></i>
                        Dublin Core Metadata
                    </h6>
                    <div class="row g-3">
                        @foreach($dcMetadata as $key => $value)
                            @if($value)
                                <div class="col-md-6">
                                    <strong class="small text-muted d-block">{{ str_replace('dc.', '', $key) }}</strong>
                                    <div class="small">{{ $value }}</div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
