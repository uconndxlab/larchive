@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Edit Taxonomy</h5>
                <a href="{{ route('admin.taxonomies.index') }}" class="btn btn-sm btn-outline-secondary">Back</a>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.taxonomies.update', $taxonomy) }}" method="POST">
                    @csrf
                    @method('PUT')
                    
                    @include('admin.taxonomies._form')

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Update Taxonomy</button>
                        <a href="{{ route('admin.taxonomies.index') }}" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
