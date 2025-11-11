@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>
        Exhibits
        @if(request('trashed') === '1')
            <span class="badge bg-danger">Deleted</span>
        @endif
    </h1>
    <div class="btn-group">
        @if(request('trashed') === '1')
            <a href="{{ route('exhibits.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back to Active
            </a>
        @else
            <a href="{{ route('exhibits.index', ['trashed' => 1]) }}" class="btn btn-outline-danger">
                <i class="bi bi-trash"></i> View Deleted
            </a>
            <a href="{{ route('exhibits.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Create Exhibit
            </a>
        @endif
    </div>
</div>

@if($exhibits->isEmpty())
    <div class="alert alert-info">
        <i class="bi bi-info-circle"></i>
        @if(request('trashed') === '1')
            No deleted exhibits.
        @else
            No exhibits yet. <a href="{{ route('exhibits.create') }}" class="alert-link">Create your first exhibit</a>.
        @endif
    </div>
@else
    <div class="row g-4">
        @foreach($exhibits as $exhibit)
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 shadow-sm {{ $exhibit->featured ? 'border-warning' : '' }} {{ $exhibit->trashed() ? 'border-danger' : '' }}">
                    @if($exhibit->cover_image)
                        <img src="{{ Storage::url($exhibit->cover_image) }}" 
                             class="card-img-top {{ $exhibit->trashed() ? 'opacity-50' : '' }}" 
                             alt="{{ $exhibit->title }}"
                             style="height: 200px; object-fit: cover;">
                    @else
                        <div class="bg-light d-flex align-items-center justify-content-center {{ $exhibit->trashed() ? 'opacity-50' : '' }}" style="height: 200px;">
                            <i class="bi bi-image text-muted" style="font-size: 3rem;"></i>
                        </div>
                    @endif
                    
                    <div class="card-body">
                        @if($exhibit->trashed())
                            <div class="mb-2">
                                <span class="badge bg-danger">
                                    <i class="bi bi-trash"></i> Deleted
                                </span>
                                <small class="text-muted">{{ $exhibit->deleted_at->diffForHumans() }}</small>
                            </div>
                        @elseif($exhibit->featured)
                            <div class="mb-2">
                                <span class="badge bg-warning text-dark">
                                    <i class="bi bi-star-fill"></i> Featured
                                </span>
                            </div>
                        @endif
                        
                        <h5 class="card-title">
                            @if($exhibit->trashed())
                                {{ $exhibit->title }}
                            @else
                                <a href="{{ route('exhibits.show', $exhibit) }}" class="text-decoration-none text-dark">
                                    {{ $exhibit->title }}
                                </a>
                            @endif
                        </h5>
                        
                        @if($exhibit->description)
                            <p class="card-text text-muted small">
                                {{ Str::limit($exhibit->description, 120) }}
                            </p>
                        @endif
                        
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <div class="small text-muted">
                                <i class="bi bi-file-earmark-text"></i> 
                                {{ $exhibit->pages_count }} {{ Str::plural('page', $exhibit->pages_count) }}
                            </div>
                            
                            @if(!$exhibit->trashed())
                                @if($exhibit->isPublished())
                                    <span class="badge bg-success">
                                        <i class="bi bi-check-circle"></i> Published
                                    </span>
                                @else
                                    <span class="badge bg-secondary">
                                        <i class="bi bi-clock"></i> Draft
                                    </span>
                                @endif
                            @endif
                        </div>
                    </div>
                    
                    <div class="card-footer bg-transparent border-top-0">
                        @if($exhibit->trashed())
                            <div class="btn-group w-100">
                                <form method="POST" action="{{ route('exhibits.restore', $exhibit->id) }}" class="flex-fill">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-success w-100">
                                        <i class="bi bi-arrow-counterclockwise"></i> Restore
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('exhibits.force-delete', $exhibit->id) }}"
                                      onsubmit="return confirm('PERMANENTLY delete this exhibit? This cannot be undone!');"
                                      class="flex-fill">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger w-100">
                                        <i class="bi bi-x-circle"></i> Delete Forever
                                    </button>
                                </form>
                            </div>
                        @else
                            <div class="btn-group w-100">
                                <a href="{{ route('exhibits.show', $exhibit) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye"></i> View
                                </a>
                                <a href="{{ route('exhibits.edit', $exhibit) }}" class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-pencil"></i> Edit
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="mt-4">
        {{ $exhibits->links() }}
    </div>
@endif
@endsection
