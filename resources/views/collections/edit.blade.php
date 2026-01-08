@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Edit Collection</h5>
                <div class="btn-group">
                    <a href="{{ route('collections.show', $collection) }}" class="btn btn-sm btn-outline-secondary">View</a>
                    <a href="{{ route('collections.index') }}" class="btn btn-sm btn-outline-secondary">Back to List</a>
                </div>
            </div>
            <div class="card-body">
                <form action="{{ route('collections.update', $collection) }}" method="POST">
                    @csrf
                    @method('PUT')
                    
                    @include('collections._form')

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Update Collection</button>
                        <a href="{{ route('collections.show', $collection) }}" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        {{-- User Tracking --}}
        @include('partials._user_tracking', ['model' => $collection])

        <div class="card border-danger mt-3">
            <div class="card-header bg-danger text-white">
                <h6 class="mb-0">Danger Zone</h6>
            </div>
            <div class="card-body">
                <p class="card-text small">Deleting this collection will not delete its items.</p>
                <form action="{{ route('collections.destroy', $collection) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this collection?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger btn-sm w-100">Delete Collection</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
