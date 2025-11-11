@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1>{{ $page->title }}</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="{{ route('exhibits.index') }}">Exhibits</a>
                </li>
                <li class="breadcrumb-item">
                    <a href="{{ route('exhibits.show', $exhibit) }}">{{ $exhibit->title }}</a>
                </li>
                @foreach($page->getBreadcrumb() as $crumb)
                    <li class="breadcrumb-item {{ $loop->last ? 'active' : '' }}">
                        @if($loop->last)
                            {{ $crumb->title }}
                        @else
                            <a href="{{ route('exhibits.pages.show', [$exhibit, $crumb]) }}">
                                {{ $crumb->title }}
                            </a>
                        @endif
                    </li>
                @endforeach
            </ol>
        </nav>
    </div>

    <div class="btn-group">
        <a href="{{ route('exhibits.pages.edit', [$exhibit, $page]) }}" class="btn btn-primary">
            <i class="bi bi-pencil"></i> Edit
        </a>
        <a href="{{ route('exhibits.show', $exhibit) }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>
</div>

<div class="row g-4">
    <div class="col-md-3">
        {{-- Page Navigation --}}
        <div class="card shadow-sm sticky-top" style="top: 1rem;">
            <div class="card-header bg-light">
                <h6 class="mb-0">
                    <i class="bi bi-list-nested"></i>
                    Navigation
                </h6>
            </div>
            <div class="list-group list-group-flush">
                <a href="{{ route('exhibits.show', $exhibit) }}" class="list-group-item list-group-item-action">
                    <i class="bi bi-house"></i> Exhibit Home
                </a>
                
                @foreach($exhibit->topLevelPages as $topPage)
                    <a href="{{ route('exhibits.pages.show', [$exhibit, $topPage]) }}" 
                       class="list-group-item list-group-item-action {{ $topPage->id === $page->id ? 'active' : '' }}">
                        {{ $topPage->title }}
                    </a>
                    
                    @if($topPage->children->count() > 0)
                        @foreach($topPage->children as $child)
                            <a href="{{ route('exhibits.pages.show', [$exhibit, $child]) }}" 
                               class="list-group-item list-group-item-action ps-4 small {{ $child->id === $page->id ? 'active' : '' }}">
                                <i class="bi bi-arrow-return-right"></i> {{ $child->title }}
                            </a>
                        @endforeach
                    @endif
                @endforeach
            </div>
        </div>
    </div>

    <div class="col-md-9">
        {{-- Main Content --}}
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                @if($page->content)
                    <div class="content-area mb-4" style="font-size: 1.1rem; line-height: 1.8;">
                        {!! nl2br(e($page->content)) !!}
                    </div>
                @endif

                {{-- Child Pages --}}
                @if($page->children->count() > 0)
                    <div class="mt-4">
                        <h5 class="mb-3">Sections</h5>
                        <div class="row g-3">
                            @foreach($page->children as $child)
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-body">
                                            <h6 class="card-title">
                                                <a href="{{ route('exhibits.pages.show', [$exhibit, $child]) }}">
                                                    {{ $child->title }}
                                                </a>
                                            </h6>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- Items Attached to This Page --}}
        @if($page->items->count() > 0)
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="mb-0">
                        <i class="bi bi-collection"></i>
                        Featured Items
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-4">
                        @foreach($page->items as $item)
                            @php
                                $layoutPosition = $item->pivot->layout_position ?? 'full';
                                $colClass = match($layoutPosition) {
                                    'left', 'right' => 'col-md-6',
                                    'gallery' => 'col-md-4',
                                    default => 'col-12'
                                };
                            @endphp
                            
                            <div class="{{ $colClass }}">
                                <div class="card h-100">
                                    @if($item->media->first())
                                        @php
                                            $media = $item->media->first();
                                            $isImage = str_starts_with($media->mime_type, 'image/');
                                        @endphp
                                        
                                        @if($isImage)
                                            <img src="{{ Storage::url($media->file_path) }}" 
                                                 class="card-img-top" 
                                                 alt="{{ $item->title }}"
                                                 style="max-height: 300px; object-fit: cover;">
                                        @endif
                                    @endif
                                    
                                    <div class="card-body">
                                        <h6 class="card-title">
                                            <a href="{{ route('items.show', $item) }}" class="text-decoration-none">
                                                {{ $item->title }}
                                            </a>
                                        </h6>
                                        
                                        @if($item->pivot->caption)
                                            <p class="card-text text-muted small">
                                                {{ $item->pivot->caption }}
                                            </p>
                                        @endif
                                        
                                        <a href="{{ route('items.show', $item) }}" class="btn btn-sm btn-outline-primary">
                                            View Item <i class="bi bi-arrow-right"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
