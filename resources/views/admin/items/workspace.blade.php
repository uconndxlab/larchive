@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Items Workspace</h1>
    @can('create', App\Models\Item::class)
        <a href="{{ route('items.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Create Item
        </a>
    @endcan
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

{{-- Status Tabs --}}
<ul class="nav nav-tabs mb-3">
    <li class="nav-item">
        <a class="nav-link {{ $status === 'draft' ? 'active' : '' }}" href="{{ route('admin.items.workspace', ['status' => 'draft']) }}">
            Draft 
            <span class="badge bg-secondary">{{ $statusCounts['draft'] }}</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ $status === 'in_review' ? 'active' : '' }}" href="{{ route('admin.items.workspace', ['status' => 'in_review']) }}">
            In Review 
            <span class="badge bg-info">{{ $statusCounts['in_review'] }}</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ $status === 'published' ? 'active' : '' }}" href="{{ route('admin.items.workspace', ['status' => 'published']) }}">
            Published 
            <span class="badge bg-success">{{ $statusCounts['published'] }}</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ $status === 'archived' ? 'active' : '' }}" href="{{ route('admin.items.workspace', ['status' => 'archived']) }}">
            Archived 
            <span class="badge bg-dark">{{ $statusCounts['archived'] }}</span>
        </a>
    </li>
</ul>

{{-- Filters --}}
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.items.workspace') }}" class="row g-3">
            <input type="hidden" name="status" value="{{ $status }}">
            
            <div class="col-md-4">
                <label for="search" class="form-label">Search</label>
                <input 
                    type="text" 
                    class="form-control" 
                    id="search" 
                    name="search" 
                    value="{{ request('search') }}"
                    placeholder="Search by title..."
                >
            </div>

            <div class="col-md-4">
                <label for="collection_id" class="form-label">Collection</label>
                <select class="form-select" id="collection_id" name="collection_id">
                    <option value="">All Collections</option>
                    @foreach($collections as $collection)
                        <option value="{{ $collection->id }}" {{ request('collection_id') == $collection->id ? 'selected' : '' }}>
                            {{ $collection->title }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-4 d-flex align-items-end">
                <button type="submit" class="btn btn-primary me-2">
                    <i class="bi bi-search"></i> Filter
                </button>
                <a href="{{ route('admin.items.workspace', ['status' => $status]) }}" class="btn btn-outline-secondary">
                    Clear
                </a>
            </div>
        </form>
    </div>
</div>

{{-- Items Grid --}}
<div class="row g-4">
    @forelse($items as $item)
        <div class="col-md-6 col-lg-4">
                <div class="card h-100">
                    {{-- Thumbnail or Type Icon --}}
                    <div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height: 150px;">
                        @if($item->media->where('is_featured', true)->first() || $item->media->first())
                            @php
                                $featuredMedia = $item->media->where('is_featured', true)->first() ?? $item->media->first();
                            @endphp
                            @if(str_starts_with($featuredMedia->mime_type, 'image/'))
                                <img src="{{ Storage::url($featuredMedia->path) }}" 
                                     alt="{{ $item->title }}" 
                                     class="img-fluid" 
                                     style="max-height: 150px; object-fit: cover; width: 100%;">
                            @else
                                <i class="bi bi-file-earmark fs-1 text-muted"></i>
                            @endif
                        @else
                            @if($item->item_type === 'audio')
                                <i class="bi bi-music-note-beamed fs-1 text-muted"></i>
                            @elseif($item->item_type === 'video')
                                <i class="bi bi-play-circle fs-1 text-muted"></i>
                            @elseif($item->item_type === 'image')
                                <i class="bi bi-image fs-1 text-muted"></i>
                            @elseif($item->item_type === 'document')
                                <i class="bi bi-file-text fs-1 text-muted"></i>
                            @else
                                <i class="bi bi-file-earmark fs-1 text-muted"></i>
                            @endif
                        @endif
                    </div>

                    <div class="card-body">
                        <h5 class="card-title text-truncate" title="{{ $item->title }}">
                            {{ $item->title }}
                        </h5>
                        
                        @if($item->description)
                            <p class="card-text text-muted small" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                {{ $item->description }}
                            </p>
                        @endif

                        <div class="mb-2">
                            <span class="badge bg-secondary me-1">{{ ucfirst($item->item_type) }}</span>
                            
                            @if($item->status === 'draft')
                                <span class="badge bg-secondary">Draft</span>
                            @elseif($item->status === 'in_review')
                                <span class="badge bg-info">In Review</span>
                            @elseif($item->status === 'published')
                                <span class="badge bg-success">Published</span>
                            @else
                                <span class="badge bg-dark">Archived</span>
                            @endif

                            @if($item->visibility === 'public')
                                <span class="badge bg-success">Public</span>
                            @elseif($item->visibility === 'authenticated')
                                <span class="badge bg-warning text-dark">Auth</span>
                            @else
                                <span class="badge bg-danger">Hidden</span>
                            @endif
                        </div>

                        <div class="small text-muted mb-3">
                            @if($item->collection)
                                <i class="bi bi-collection"></i> {{ $item->collection->title }}<br>
                            @endif
                            <i class="bi bi-clock"></i> {{ $item->updated_at->diffForHumans() }}
                        </div>

                        <div class="d-grid gap-2">
                            @can('update', $item)
                                <a href="{{ route('items.edit', $item) }}" class="btn btn-primary">
                                    <i class="bi bi-pencil"></i> Edit Item
                                </a>
                            @endcan
                            <div class="btn-group">
                                @can('view', $item)
                                    <a href="{{ route('items.show', $item) }}" class="btn btn-outline-secondary btn-sm">
                                        <i class="bi bi-eye"></i> View
                                    </a>
                                @endcan
                                @can('delete', $item)
                                    <form action="{{ route('items.destroy', $item) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this item?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger btn-sm">
                                            <i class="bi bi-trash"></i> Delete
                                        </button>
                                    </form>
                                @endcan
                            </div>
                        </div>
                    </div>
                </div>
            </div>
    @empty
        <div class="col-12">
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i> No items found with status "{{ $status }}".
            </div>
        </div>
    @endforelse
</div>

@if($items->hasPages())
    <div class="mt-4">
        {{ $items->links() }}
    </div>
@endif
@endsection
