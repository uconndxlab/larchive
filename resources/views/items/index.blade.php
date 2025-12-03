@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Items</h1>
    <a href="{{ route('items.create') }}" class="btn btn-primary">Create Item</a>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form hx-get="{{ route('items.index') }}" hx-target="#items-table-body" hx-trigger="input changed delay:300ms from:#search, change from:#collection_filter, change from:#tag_filter, submit" hx-swap="innerHTML">
            <div class="row g-3">
                <div class="col-md-6">
                    <input type="text" name="search" id="search" class="form-control" placeholder="Search items..." value="{{ request('search') }}">
                </div>
                <div class="col-md-3">
                    <select name="collection_id" id="collection_filter" class="form-select">
                        <option value="">All Collections</option>
                        @foreach(App\Models\Collection::orderBy('title')->get() as $collection)
                            <option value="{{ $collection->id }}" {{ request('collection_id') == $collection->id ? 'selected' : '' }}>
                                {{ $collection->title }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="tag_id" id="tag_filter" class="form-select">
                        <option value="">All Tags</option>
                        @foreach($tags as $tag)
                            <option value="{{ $tag->id }}" {{ request('tag_id') == $tag->id ? 'selected' : '' }}>
                                {{ $tag->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Slug</th>
                        <th>Collection</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Updated</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody id="items-table-body">
                    @include('items._table')
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
