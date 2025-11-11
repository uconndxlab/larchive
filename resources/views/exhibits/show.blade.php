@extends('layouts.app')

@section('content')
<div class="mb-4">
    <div class="d-flex justify-content-between align-items-start mb-3">
        <div class="flex-grow-1">
            @if($exhibit->featured)
                <div class="mb-2">
                    <span class="badge bg-warning text-dark">
                        <i class="bi bi-star-fill"></i> Featured Exhibit
                    </span>
                </div>
            @endif
            
            <h1 class="mb-2">{{ $exhibit->title }}</h1>
            
            <div class="d-flex align-items-center gap-2 mb-3">
                @if($exhibit->isPublished())
                    <span class="badge bg-success">
                        <i class="bi bi-check-circle"></i> Published
                    </span>
                @else
                    <span class="badge bg-secondary">
                        <i class="bi bi-clock"></i> Draft
                    </span>
                @endif
                
                <span class="text-muted small">
                    <i class="bi bi-file-earmark-text"></i>
                    {{ $exhibit->pages->count() }} {{ Str::plural('page', $exhibit->pages->count()) }}
                </span>
            </div>

            @if($exhibit->description)
                <div class="alert alert-light border">
                    <p class="mb-0">{{ $exhibit->description }}</p>
                </div>
            @endif

            @if($exhibit->credits)
                <div class="small text-muted mb-3">
                    <i class="bi bi-people"></i>
                    <strong>Credits:</strong> {{ $exhibit->credits }}
                </div>
            @endif
        </div>

        <div class="btn-group ms-3">
            <a href="{{ route('exhibits.edit', $exhibit) }}" class="btn btn-primary">
                <i class="bi bi-pencil"></i> Edit
            </a>
            <button type="button" class="btn btn-outline-secondary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown">
                <span class="visually-hidden">Toggle Dropdown</span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li>
                    <a class="dropdown-item" href="{{ route('exhibits.pages.create', $exhibit) }}">
                        <i class="bi bi-plus-circle"></i> Add Page
                    </a>
                </li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <form method="POST" action="{{ route('exhibits.destroy', $exhibit) }}" 
                          onsubmit="return confirm('Are you sure you want to delete this exhibit?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="dropdown-item text-danger">
                            <i class="bi bi-trash"></i> Delete Exhibit
                        </button>
                    </form>
                </li>
            </ul>
        </div>
    </div>

    @if($exhibit->cover_image)
        <div class="mb-4">
            <img src="{{ Storage::url($exhibit->cover_image) }}" 
                 class="img-fluid rounded shadow-sm" 
                 alt="{{ $exhibit->title }}"
                 style="max-height: 400px; width: 100%; object-fit: cover;">
        </div>
    @endif
</div>

<div class="row g-4">
    {{-- Table of Contents / Page Navigation --}}
    <div class="col-md-4 col-lg-3">
        <div class="card shadow-sm sticky-top" style="top: 1rem;">
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                <h6 class="mb-0">
                    <i class="bi bi-list-nested"></i>
                    Pages
                </h6>
                <a href="{{ route('exhibits.pages.create', $exhibit) }}" 
                   class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-plus"></i>
                </a>
            </div>
            <div class="list-group list-group-flush">
                @forelse($exhibit->topLevelPages as $page)
                    <a href="#page-{{ $page->id }}" 
                       class="list-group-item list-group-item-action">
                        {{ $page->title }}
                        @if($page->children->count() > 0)
                            <span class="badge bg-secondary float-end">
                                {{ $page->children->count() }}
                            </span>
                        @endif
                    </a>
                    
                    @if($page->children->count() > 0)
                        @foreach($page->children as $child)
                            <a href="#page-{{ $child->id }}" 
                               class="list-group-item list-group-item-action ps-4 small">
                                <i class="bi bi-arrow-return-right"></i> {{ $child->title }}
                            </a>
                        @endforeach
                    @endif
                @empty
                    <div class="list-group-item text-muted small">
                        <i class="bi bi-info-circle"></i>
                        No pages yet
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Main Content Area --}}
    <div class="col-md-8 col-lg-9">
        @forelse($exhibit->topLevelPages as $page)
            <div id="page-{{ $page->id }}" class="card shadow-sm mb-4">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">{{ $page->title }}</h4>
                    <div class="btn-group btn-group-sm">
                        <a href="{{ route('exhibits.pages.show', [$exhibit, $page]) }}" 
                           class="btn btn-outline-secondary">
                            <i class="bi bi-eye"></i>
                        </a>
                        <a href="{{ route('exhibits.pages.edit', [$exhibit, $page]) }}" 
                           class="btn btn-outline-primary">
                            <i class="bi bi-pencil"></i>
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @if($page->content)
                        <div class="mb-3">
                            {!! nl2br(e($page->content)) !!}
                        </div>
                    @endif

                    @if($page->items->count() > 0)
                        <div class="row g-3">
                            @foreach($page->items as $item)
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-body">
                                            <h6 class="card-title">
                                                <a href="{{ route('items.show', $item) }}">
                                                    {{ $item->title }}
                                                </a>
                                            </h6>
                                            @if($item->pivot->caption)
                                                <p class="card-text small text-muted">
                                                    {{ $item->pivot->caption }}
                                                </p>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            {{-- Render child pages --}}
            @foreach($page->children as $child)
                <div id="page-{{ $child->id }}" class="card shadow-sm mb-4 ms-4">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="bi bi-arrow-return-right"></i>
                            {{ $child->title }}
                        </h5>
                        <div class="btn-group btn-group-sm">
                            <a href="{{ route('exhibits.pages.show', [$exhibit, $child]) }}" 
                               class="btn btn-outline-secondary">
                                <i class="bi bi-eye"></i>
                            </a>
                            <a href="{{ route('exhibits.pages.edit', [$exhibit, $child]) }}" 
                               class="btn btn-outline-primary">
                                <i class="bi bi-pencil"></i>
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        @if($child->content)
                            <div class="mb-3">
                                {!! nl2br(e($child->content)) !!}
                            </div>
                        @endif

                        @if($child->items->count() > 0)
                            <div class="row g-3">
                                @foreach($child->items as $item)
                                    <div class="col-md-6">
                                        <div class="card">
                                            <div class="card-body">
                                                <h6 class="card-title">
                                                    <a href="{{ route('items.show', $item) }}">
                                                        {{ $item->title }}
                                                    </a>
                                                </h6>
                                                @if($item->pivot->caption)
                                                    <p class="card-text small text-muted">
                                                        {{ $item->pivot->caption }}
                                                    </p>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        @empty
            <div class="card shadow-sm">
                <div class="card-body text-center text-muted py-5">
                    <i class="bi bi-file-earmark-plus" style="font-size: 3rem;"></i>
                    <h5 class="mt-3">No pages yet</h5>
                    <p>Get started by creating the first page for this exhibit.</p>
                    <a href="{{ route('exhibits.pages.create', $exhibit) }}" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> Create First Page
                    </a>
                </div>
            </div>
        @endforelse
    </div>
</div>
@endsection
