@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Collections</h1>
    <a href="{{ route('collections.create') }}" class="btn btn-primary">Create Collection</a>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form hx-get="{{ route('collections.index') }}" hx-target="#collections-table-body" hx-trigger="input changed delay:300ms from:#search, submit" hx-swap="innerHTML">
            <div class="input-group">
                <input type="text" name="search" id="search" class="form-control" placeholder="Search collections..." value="{{ request('search') }}">
                <button type="submit" class="btn btn-outline-secondary">Search</button>
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
                        <th>Status</th>
                        <th>Updated</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody id="collections-table-body">
                    @include('collections._table')
                </tbody>
            </table>
        </div>

        @if($collections->hasPages())
            <div class="mt-3">
                {{ $collections->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
