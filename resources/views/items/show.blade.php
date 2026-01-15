@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        {{-- Left Column: Featured Image and Title --}}
        <div class="col-md-3">
            {{-- Featured/First Image --}}
            <div class="mb-3">
                @php
                    $featuredMedia = $item->featuredImage;
                @endphp
                @if($featuredMedia && str_starts_with($featuredMedia->mime_type, 'image/'))
                    <img src="{{ Storage::url($featuredMedia->path) }}" class="img-fluid" alt="{{ $item->title }}">
                @else
                    <div class="bg-light border d-flex align-items-center justify-content-center" style="aspect-ratio: 1; min-height: 200px;">
                        <i class="bi bi-file-earmark fs-1 text-muted"></i>
                    </div>
                @endif
            </div>
            
            <h2>{{ $item->title }}</h2>
            
            @if($item->description)
                <p class="text-muted">{{ $item->description }}</p>
            @endif

            {{-- Edit/Back Buttons --}}
            <div class="d-flex gap-2 mb-3">
                @can('update', $item)
                    <a href="{{ route('items.edit', $item) }}" class="btn btn-sm btn-primary">
                        <i class="bi bi-pencil"></i> Edit
                    </a>
                @endcan
                <a href="{{ route('items.index') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Back
                </a>
            </div>

            {{-- Badges --}}
            <div class="mb-3">
                <span class="badge bg-{{ $item->item_type === 'audio' ? 'primary' : ($item->item_type === 'video' ? 'danger' : ($item->item_type === 'image' ? 'success' : ($item->item_type === 'document' ? 'warning' : 'secondary'))) }}">
                    @switch($item->item_type)
                        @case('audio') <i class="bi bi-music-note"></i> Audio @break
                        @case('video') <i class="bi bi-camera-video"></i> Video @break
                        @case('image') <i class="bi bi-image"></i> Image @break
                        @case('document') <i class="bi bi-file-earmark-text"></i> Document @break
                        @default <i class="bi bi-file-earmark"></i> {{ ucfirst($item->item_type) }}
                    @endswitch
                </span>
                @if($item->published_at)
                    <span class="badge bg-success">Published</span>
                @else
                    <span class="badge bg-secondary">Draft</span>
                @endif
            </div>

            {{-- Tags/Terms --}}
            @if($item->terms->isNotEmpty())
                @php
                    $termsByTaxonomy = $item->terms->groupBy('taxonomy.name');
                @endphp
                @foreach($termsByTaxonomy as $taxonomyName => $terms)
                    <div class="mb-2">
                        <strong class="small text-muted">{{ $taxonomyName }}:</strong><br>
                        @foreach($terms as $term)
                            <a href="{{ route('terms.show', [$term->taxonomy, $term]) }}" 
                               class="badge bg-secondary text-decoration-none me-1">
                                {{ $term->name }}
                            </a>
                        @endforeach
                    </div>
                @endforeach
            @endif

            @if($item->collection)
                <div class="mb-2">
                    <strong class="small text-muted">Collection:</strong><br>
                    <a href="{{ route('collections.show', $item->collection) }}" class="badge bg-light text-dark text-decoration-none border">
                        <i class="bi bi-folder"></i> {{ $item->collection->title }}
                    </a>
                </div>
            @endif
        </div>

        {{-- Right Column: Media Player --}}
        <div class="col-md-9">
            {{-- Type-specific media display --}}
            @switch($item->item_type)
                @case('audio')
                    @php
                        $audioFiles = $item->media->filter(fn($m) => str_starts_with($m->mime_type, 'audio/') && !isset($m->metadata['role']));
                    @endphp
                    @if($audioFiles->isNotEmpty())
                        @include('partials.audio-player', ['audioFiles' => $audioFiles])
                    @else
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle"></i> No audio files attached yet.
                        </div>
                    @endif
                    @break
                
                @case('video')
                    @php
                        $videoFiles = $item->media->filter(fn($m) => str_starts_with($m->mime_type, 'video/') && !isset($m->metadata['role']));
                    @endphp
                    @if($videoFiles->isNotEmpty())
                        @foreach($videoFiles as $video)
                            <video controls class="w-100 mb-3">
                                <source src="{{ Storage::url($video->path) }}" type="{{ $video->mime_type }}">
                            </video>
                        @endforeach
                    @else
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle"></i> No video files attached yet.
                        </div>
                    @endif
                    @break
                
                @case('image')
                    @php
                        $imageFiles = $item->media->filter(fn($m) => str_starts_with($m->mime_type, 'image/') && !isset($m->metadata['role']));
                    @endphp
                    @if($imageFiles->isNotEmpty())
                        <div class="row g-3">
                            @foreach($imageFiles as $image)
                                <div class="col-12">
                                    <img src="{{ Storage::url($image->path) }}" class="img-fluid" alt="{{ $image->alt_text }}">
                                </div>
                            @endforeach
                        </div>
                    @endif
                    @break
                
                @case('document')
                    @php
                        $docFiles = $item->media->filter(fn($m) => !isset($m->metadata['role']));
                    @endphp
                    @if($docFiles->isNotEmpty())
                        <div class="list-group">
                            @foreach($docFiles as $doc)
                                <a href="{{ Storage::url($doc->path) }}" class="list-group-item list-group-item-action" target="_blank">
                                    <i class="bi bi-file-earmark"></i> {{ $doc->filename }}
                                </a>
                            @endforeach
                        </div>
                    @endif
                    @break
            @endswitch
        </div>
    </div>

    {{-- Tabs Navigation --}}
    <ul class="nav nav-tabs mb-3">
        <li class="nav-item">
            <a class="nav-link active" href="#item-details" data-bs-toggle="tab">
                <i class="bi bi-info-circle"></i> Item Details
            </a>
        </li>
        @if(!empty($item->ohms_json))
            <li class="nav-item">
                <a class="nav-link" href="#transcript" data-bs-toggle="tab">
                    <i class="bi bi-file-text"></i> Transcript
                </a>
            </li>
        @endif
        @if($item->media->filter(fn($m) => isset($m->metadata['role']))->isNotEmpty())
            <li class="nav-item">
                <a class="nav-link" href="#resources" data-bs-toggle="tab">
                    <i class="bi bi-paperclip"></i> Resources
                </a>
            </li>
        @endif
    </ul>

    {{-- Tab Content --}}
    <div class="tab-content">
        {{-- Item Details Tab --}}
        <div class="tab-pane fade show active" id="item-details">
            <div class="row">
                {{-- Left Column: OHMS Index/Segments or empty --}}
                <div class="col-md-4">
                    @if(!empty($item->ohms_json) && !empty($item->ohms_json['segments']))
                        <h5 class="mb-3">Index</h5>
                        <div class="list-group">
                            @foreach($item->ohms_json['segments'] as $segment)
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h6 class="mb-1">{{ $segment['title'] ?? 'Untitled' }}</h6>
                                        <span class="badge bg-light text-dark">
                                            [{{ gmdate('H:i:s', $segment['start_time'] ?? 0) }}]
                                        </span>
                                    </div>
                                    @if(!empty($segment['synopsis']))
                                        <p class="mb-2 small text-muted">{{ $segment['synopsis'] }}</p>
                                    @endif
                                    @if(!empty($segment['keywords']))
                                        <div>
                                            @foreach(explode(';', $segment['keywords']) as $keyword)
                                                <span class="badge bg-primary">{{ trim($keyword) }}</span>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- Right Column: Item Details --}}
                <div class="col-md-8">
                    <h3>Item Details</h3>
                    
                    <div class="row g-4 mb-4">
                        <div class="col-md-6">
                            <h6 class="text-muted text-uppercase small mb-3">Basic Information</h6>
                            <dl class="row mb-0">
                                <dt class="col-sm-4 small text-muted">Identifier</dt>
                                <dd class="col-sm-8"><code>{{ $item->slug }}</code></dd>

                                <dt class="col-sm-4 small text-muted">Type</dt>
                                <dd class="col-sm-8">{{ ucfirst($item->item_type) }}</dd>

                                <dt class="col-sm-4 small text-muted">Collection</dt>
                                <dd class="col-sm-8">
                                    @if($item->collection)
                                        <a href="{{ route('collections.show', $item->collection) }}">
                                            {{ $item->collection->title }}
                                        </a>
                                    @else
                                        <span class="text-muted">Uncategorized</span>
                                    @endif
                                </dd>

                                <dt class="col-sm-4 small text-muted">Status</dt>
                                <dd class="col-sm-8">
                                    @if($item->published_at)
                                        Published on {{ $item->published_at->format('M d, Y') }}
                                    @else
                                        Draft
                                    @endif
                                </dd>
                            </dl>
                        </div>

                        <div class="col-md-6">
                            <h6 class="text-muted text-uppercase small mb-3">System Information</h6>
                            <dl class="row mb-0">
                                <dt class="col-sm-4 small text-muted">Created</dt>
                                <dd class="col-sm-8">
                                    {{ $item->created_at->format('M d, Y') }}<br>
                                    <small class="text-muted">{{ $item->created_at->format('g:i A') }}</small>
                                </dd>

                                <dt class="col-sm-4 small text-muted">Last Updated</dt>
                                <dd class="col-sm-8">
                                    {{ $item->updated_at->format('M d, Y') }}<br>
                                    <small class="text-muted">{{ $item->updated_at->format('g:i A') }}</small>
                                </dd>

                                @if(!empty($item->ohms_json))
                                    <dt class="col-sm-4 small text-muted">OHMS Data</dt>
                                    <dd class="col-sm-8">
                                        <span class="badge bg-primary">
                                            <i class="bi bi-mic-fill"></i>
                                            {{ count($item->ohms_json['segments'] ?? []) }} segments
                                        </span>
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
                        <h6 class="text-muted text-uppercase small mb-3">Dublin Core Metadata</h6>
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

        {{-- Transcript Tab --}}
        @if(!empty($item->ohms_json))
            <div class="tab-pane fade" id="transcript">
                <div class="card">
                    <div class="card-body">
                        @if(!empty($item->ohms_json['transcript']))
                            <div style="white-space: pre-wrap; font-family: monospace;">{{ $item->ohms_json['transcript'] }}</div>
                        @else
                            <p class="text-muted">No transcript available.</p>
                        @endif
                    </div>
                </div>
            </div>
        @endif

        {{-- Resources Tab --}}
        @if($item->media->filter(fn($m) => isset($m->metadata['role']))->isNotEmpty())
            <div class="tab-pane fade" id="resources">
                <h5 class="mb-3">Supplemental Files</h5>
                <div class="list-group">
                    @foreach($item->media->filter(fn($m) => isset($m->metadata['role'])) as $resource)
                        <a href="{{ Storage::url($resource->path) }}" 
                           class="list-group-item list-group-item-action"
                           target="_blank">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1">
                                        <i class="bi bi-file-earmark"></i>
                                        {{ $resource->metadata['label'] ?? $resource->filename }}
                                    </h6>
                                    @if(!empty($resource->metadata['role']))
                                        <small class="text-muted">{{ $resource->metadata['role'] }}</small>
                                    @endif
                                </div>
                                <div>
                                    <span class="badge bg-secondary">
                                        {{ strtoupper(pathinfo($resource->filename, PATHINFO_EXTENSION)) }}
                                    </span>
                                    @if(!empty($resource->metadata['visibility']) && $resource->metadata['visibility'] !== 'public')
                                        <span class="badge bg-warning">
                                            {{ ucfirst($resource->metadata['visibility']) }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
