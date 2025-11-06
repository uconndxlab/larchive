@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>{{ $collection->title }}</h1>
    <div class="btn-group">
        <a href="{{ route('collections.edit', $collection) }}" class="btn btn-primary">Edit</a>
        <a href="{{ route('collections.index') }}" class="btn btn-outline-secondary">Back to List</a>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Details</h5>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-3">Slug</dt>
                    <dd class="col-sm-9"><code>{{ $collection->slug }}</code></dd>

                    <dt class="col-sm-3">Status</dt>
                    <dd class="col-sm-9">
                        @if($collection->published_at)
                            <span class="badge bg-success">Published</span>
                            <small class="text-muted ms-2">{{ $collection->published_at->format('M d, Y') }}</small>
                        @else
                            <span class="badge bg-secondary">Draft</span>
                        @endif
                    </dd>

                    @if($collection->description)
                        <dt class="col-sm-3">Description</dt>
                        <dd class="col-sm-9">{{ $collection->description }}</dd>
                    @endif

                    <dt class="col-sm-3">Created</dt>
                    <dd class="col-sm-9">{{ $collection->created_at->format('M d, Y g:i A') }}</dd>

                    <dt class="col-sm-3">Updated</dt>
                    <dd class="col-sm-9">{{ $collection->updated_at->format('M d, Y g:i A') }}</dd>
                </dl>
            </div>
        </div>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Items ({{ $collection->items->count() }})</h5>
                <a href="{{ route('items.create', ['collection_id' => $collection->id]) }}" class="btn btn-sm btn-primary">Add Item</a>
            </div>
            <div class="card-body">
                @forelse($collection->items as $item)
                    <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-2">
                        <div>
                            <h6 class="mb-1">
                                <a href="{{ route('items.show', $item) }}">{{ $item->title }}</a>
                            </h6>
                            <small class="text-muted">
                                <code>{{ $item->slug }}</code>
                                @if($item->published_at)
                                    <span class="badge bg-success ms-2">Published</span>
                                @else
                                    <span class="badge bg-secondary ms-2">Draft</span>
                                @endif
                            </small>
                        </div>
                        <div class="btn-group btn-group-sm">
                            <a href="{{ route('items.edit', $item) }}" class="btn btn-outline-secondary">Edit</a>
                        </div>
                    </div>
                @empty
                    <p class="text-muted mb-0">No items in this collection yet.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
