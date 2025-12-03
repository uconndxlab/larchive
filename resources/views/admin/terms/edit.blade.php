@extends('layouts.app')

@section('content')
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.taxonomies.index') }}">Taxonomies</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.terms.index', $taxonomy) }}">{{ $taxonomy->name }}</a></li>
        <li class="breadcrumb-item active">Edit {{ $term->name }}</li>
    </ol>
</nav>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Edit Term</h5>
                <a href="{{ route('admin.terms.index', $taxonomy) }}" class="btn btn-sm btn-outline-secondary">Back</a>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.terms.update', [$taxonomy, $term]) }}" method="POST">
                    @csrf
                    @method('PUT')
                    
                    @include('admin.terms._form')

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Update Term</button>
                        <a href="{{ route('admin.terms.index', $taxonomy) }}" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
