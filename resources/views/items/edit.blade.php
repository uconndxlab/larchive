@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Edit Item</h5>
                <div class="btn-group">
                    <a href="{{ route('items.show', $item) }}" class="btn btn-sm btn-outline-secondary">View</a>
                    <a href="{{ route('items.index') }}" class="btn btn-sm btn-outline-secondary">Back to List</a>
                </div>
            </div>
            <div class="card-body">
                <form action="{{ route('items.update', $item) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    
                    @include('items._form')

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Update Item</button>
                        <a href="{{ route('items.show', $item) }}" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>

        @include('items._media')
    </div>

    <div class="col-md-4">
        @include('items._ohms_upload')

        <div class="card border-danger">
            <div class="card-header bg-danger text-white">
                <h6 class="mb-0">Danger Zone</h6>
            </div>
            <div class="card-body">
                <p class="card-text small">Deleting this item will also delete all associated media and metadata.</p>
                <form action="{{ route('items.destroy', $item) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this item and all its media/metadata?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger btn-sm w-100">Delete Item</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
