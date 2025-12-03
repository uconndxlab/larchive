@extends('layouts.app')

@section('content')
<div class="mb-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
            <li class="breadcrumb-item">{{ $taxonomy->name }}</li>
            <li class="breadcrumb-item active">{{ $term->name }}</li>
        </ol>
    </nav>

    <div class="d-flex align-items-start gap-3">
        <div class="flex-grow-1">
            <h1>{{ $term->name }}</h1>
            @if($term->description)
                <p class="lead text-muted">{{ $term->description }}</p>
            @endif
            <p class="text-muted">
                <small>{{ $taxonomy->name }}</small>
            </p>
        </div>
    </div>
</div>

{{-- Items --}}
@if($items->total() > 0)
    <section class="mb-5">
        <h2 class="h4 mb-3">Items ({{ $items->total() }})</h2>
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
            @foreach($items as $item)
                <div class="col">
                    <div class="card h-100">
                        @if($item->media->first())
                            <img src="{{ asset('storage/' . $item->media->first()->file_path) }}" 
                                 class="card-img-top" 
                                 alt="{{ $item->title }}"
                                 style="height: 200px; object-fit: cover;">
                        @endif
                        <div class="card-body">
                            <h5 class="card-title">
                                <a href="{{ route('items.show', $item) }}" class="text-decoration-none">
                                    {{ $item->title }}
                                </a>
                            </h5>
                            @if($item->description)
                                <p class="card-text text-muted small">
                                    {{ Str::limit($item->description, 100) }}
                                </p>
                            @endif
                            @if($item->collection)
                                <p class="card-text">
                                    <small class="text-muted">
                                        Collection: <a href="{{ route('collections.show', $item->collection) }}">{{ $item->collection->title }}</a>
                                    </small>
                                </p>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        
        @if($items->hasPages())
            <div class="mt-4">
                {{ $items->links() }}
            </div>
        @endif
    </section>
@endif

{{-- Collections --}}
@if($collections->total() > 0)
    <section class="mb-5">
        <h2 class="h4 mb-3">Collections ({{ $collections->total() }})</h2>
        <div class="list-group">
            @foreach($collections as $collection)
                <a href="{{ route('collections.show', $collection) }}" class="list-group-item list-group-item-action">
                    <div class="d-flex w-100 justify-content-between">
                        <h5 class="mb-1">{{ $collection->title }}</h5>
                        <small class="text-muted">{{ $collection->items_count }} items</small>
                    </div>
                    @if($collection->description)
                        <p class="mb-1 text-muted">{{ Str::limit($collection->description, 150) }}</p>
                    @endif
                </a>
            @endforeach
        </div>
        
        @if($collections->hasPages())
            <div class="mt-4">
                {{ $collections->links() }}
            </div>
        @endif
    </section>
@endif

{{-- Exhibits --}}
@if($exhibits->total() > 0)
    <section class="mb-5">
        <h2 class="h4 mb-3">Exhibits ({{ $exhibits->total() }})</h2>
        <div class="list-group">
            @foreach($exhibits as $exhibit)
                <a href="{{ route('exhibits.show', $exhibit) }}" class="list-group-item list-group-item-action">
                    <div class="d-flex w-100 justify-content-between">
                        <h5 class="mb-1">{{ $exhibit->title }}</h5>
                        @if($exhibit->featured)
                            <span class="badge bg-primary">Featured</span>
                        @endif
                    </div>
                    @if($exhibit->description)
                        <p class="mb-1 text-muted">{{ Str::limit($exhibit->description, 150) }}</p>
                    @endif
                </a>
            @endforeach
        </div>
        
        @if($exhibits->hasPages())
            <div class="mt-4">
                {{ $exhibits->links() }}
            </div>
        @endif
    </section>
@endif

{{-- No results --}}
@if($items->total() === 0 && $collections->total() === 0 && $exhibits->total() === 0)
    <div class="alert alert-info">
        <p class="mb-0">No resources are currently tagged with <strong>{{ $term->name }}</strong>.</p>
    </div>
@endif

@endsection
