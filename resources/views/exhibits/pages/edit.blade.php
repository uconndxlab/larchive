@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1>Edit Page: {{ $page->title }}</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="{{ route('exhibits.index') }}">Exhibits</a>
                </li>
                <li class="breadcrumb-item">
                    <a href="{{ route('exhibits.show', $exhibit) }}">{{ $exhibit->title }}</a>
                </li>
                <li class="breadcrumb-item active">Edit Page</li>
            </ol>
        </nav>
    </div>
</div>

<form method="POST" action="{{ route('exhibits.pages.update', [$exhibit, $page]) }}">
    @csrf
    @method('PATCH')
    
    <div class="row">
        <div class="col-md-8">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Page Content</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
                        <input type="text" 
                               class="form-control @error('title') is-invalid @enderror" 
                               id="title" 
                               name="title" 
                               value="{{ old('title', $page->title) }}" 
                               required>
                        @error('title')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="slug" class="form-label">Slug <span class="text-danger">*</span></label>
                        <input type="text" 
                               class="form-control @error('slug') is-invalid @enderror" 
                               id="slug" 
                               name="slug" 
                               value="{{ old('slug', $page->slug) }}"
                               required>
                        @error('slug')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="visibility" class="form-label">Visibility <span class="text-danger">*</span></label>
                        <select 
                            class="form-select @error('visibility') is-invalid @enderror" 
                            id="visibility" 
                            name="visibility" 
                            required
                        >
                            <option value="public" {{ old('visibility', $page->visibility ?? 'authenticated') == 'public' ? 'selected' : '' }}>
                                Public - Visible to everyone
                            </option>
                            <option value="authenticated" {{ old('visibility', $page->visibility ?? 'authenticated') == 'authenticated' ? 'selected' : '' }}>
                                Authenticated - Requires login
                            </option>
                            <option value="hidden" {{ old('visibility', $page->visibility ?? 'authenticated') == 'hidden' ? 'selected' : '' }}>
                                Hidden - Admin only
                            </option>
                        </select>
                        <small class="text-muted">Control who can view this page</small>
                        @error('visibility')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="content" class="form-label">Content</label>
                        <textarea class="form-control @error('content') is-invalid @enderror" 
                                  id="content" 
                                  name="content" 
                                  rows="12">{{ old('content', $page->content) }}</textarea>
                        @error('content')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            {{-- Items Management --}}
            <div class="card shadow-sm">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="bi bi-collection"></i>
                        Attached Items
                    </h5>
                    <button type="button" 
                            class="btn btn-sm btn-primary" 
                            data-bs-toggle="modal" 
                            data-bs-target="#attachItemModal">
                        <i class="bi bi-plus-circle"></i> Attach Item
                    </button>
                </div>
                <div class="card-body" id="items-list">
                    @if($page->items->count() > 0)
                        <div class="list-group">
                            @foreach($page->items as $item)
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1">{{ $item->title }}</h6>
                                            <div class="small text-muted mb-2">
                                                Layout: <span class="badge bg-secondary">{{ $item->pivot->layout_position }}</span>
                                            </div>
                                            @if($item->pivot->caption)
                                                <p class="small mb-0">{{ $item->pivot->caption }}</p>
                                            @endif
                                        </div>
                                        <form method="POST" 
                                              action="{{ route('exhibits.pages.items.detach', [$exhibit, $page, $item]) }}"
                                              hx-post="{{ route('exhibits.pages.items.detach', [$exhibit, $page, $item]) }}"
                                              hx-target="#items-list"
                                              hx-swap="outerHTML"
                                              onsubmit="return confirm('Remove this item from the page?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <i class="bi bi-x"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-muted mb-0">
                            <i class="bi bi-info-circle"></i>
                            No items attached yet. Click "Attach Item" to add items to this page.
                        </p>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Page Settings</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="parent_id" class="form-label">Parent Page</label>
                        <select class="form-select @error('parent_id') is-invalid @enderror" 
                                id="parent_id" 
                                name="parent_id">
                            <option value="">None (Top Level)</option>
                            @foreach($exhibit->topLevelPages as $topPage)
                                @if($topPage->id !== $page->id)
                                    <option value="{{ $topPage->id }}" 
                                            {{ old('parent_id', $page->parent_id) == $topPage->id ? 'selected' : '' }}>
                                        {{ $topPage->title }}
                                    </option>
                                @endif
                            @endforeach
                        </select>
                        <small class="text-muted">Make this a section under another page</small>
                        @error('parent_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            @if($page->children->count() > 0)
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-light">
                        <h6 class="mb-0">
                            <i class="bi bi-list-nested"></i>
                            Sub-pages ({{ $page->children->count() }})
                        </h6>
                    </div>
                    <div class="list-group list-group-flush">
                        @foreach($page->children as $child)
                            <a href="{{ route('exhibits.pages.edit', [$exhibit, $child]) }}" 
                               class="list-group-item list-group-item-action">
                                {{ $child->title }}
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>

    <div class="d-flex justify-content-between mt-4">
        <button type="button" class="btn btn-danger"
                onclick="if(confirm('Are you sure you want to delete this page?')) { document.getElementById('delete-page-form').submit(); }">
            <i class="bi bi-trash"></i> Delete Page
        </button>
        
        <div>
            <a href="{{ route('exhibits.pages.show', [$exhibit, $page]) }}" class="btn btn-outline-secondary me-2">Cancel</a>
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-save"></i> Update Page
            </button>
        </div>
    </div>
</form>

{{-- Separate delete form outside main form --}}
<form id="delete-page-form" method="POST" action="{{ route('exhibits.pages.destroy', [$exhibit, $page]) }}" class="d-none">
    @csrf
    @method('DELETE')
</form>

{{-- Modal for Attaching Items --}}
<div class="modal fade" id="attachItemModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Attach Item to Page</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" 
                  action="{{ route('exhibits.pages.items.attach', [$exhibit, $page]) }}"
                  hx-post="{{ route('exhibits.pages.items.attach', [$exhibit, $page]) }}"
                  hx-target="#items-list"
                  hx-swap="outerHTML">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="item_id" class="form-label">Select Item</label>
                        <select class="form-select" id="item_id" name="item_id" required>
                            <option value="">Choose an item...</option>
                            @foreach($availableItems as $item)
                                <option value="{{ $item->id }}">{{ $item->title }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="layout_position" class="form-label">Layout Position</label>
                        <select class="form-select" id="layout_position" name="layout_position" required>
                            <option value="full">Full Width</option>
                            <option value="left">Left Column</option>
                            <option value="right">Right Column</option>
                            <option value="gallery">Gallery Grid</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="caption" class="form-label">Caption (optional)</label>
                        <textarea class="form-control" 
                                  id="caption" 
                                  name="caption" 
                                  rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Attach Item</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
