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

{{-- Items Table --}}
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Collection</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Visibility</th>
                        <th>Updated</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $item)
                        <tr>
                            <td>
                                <strong>{{ $item->title }}</strong>
                            </td>
                            <td>
                                @if($item->collection)
                                    {{ $item->collection->title }}
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-secondary">{{ ucfirst($item->item_type) }}</span>
                            </td>
                            <td>
                                @if($item->status === 'draft')
                                    <span class="badge bg-secondary">Draft</span>
                                @elseif($item->status === 'in_review')
                                    <span class="badge bg-info">In Review</span>
                                @elseif($item->status === 'published')
                                    <span class="badge bg-success">Published</span>
                                @else
                                    <span class="badge bg-dark">Archived</span>
                                @endif
                            </td>
                            <td>
                                @if($item->visibility === 'public')
                                    <span class="badge bg-success">Public</span>
                                @elseif($item->visibility === 'authenticated')
                                    <span class="badge bg-warning">Authenticated</span>
                                @else
                                    <span class="badge bg-danger">Hidden</span>
                                @endif
                            </td>
                            <td>{{ $item->updated_at->diffForHumans() }}</td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    @can('view', $item)
                                        <a href="{{ route('items.show', $item) }}" class="btn btn-outline-info" title="View">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    @endcan
                                    @can('update', $item)
                                        <a href="{{ route('items.edit', $item) }}" class="btn btn-outline-secondary" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                    @endcan
                                    @can('delete', $item)
                                        <form action="{{ route('items.destroy', $item) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this item?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger btn-sm" title="Delete">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                No items found with status "{{ $status }}".
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($items->hasPages())
            <div class="mt-3">
                {{ $items->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
