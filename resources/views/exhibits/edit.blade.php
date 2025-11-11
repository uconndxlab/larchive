@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Edit Exhibit: {{ $exhibit->title }}</h1>
    <a href="{{ route('exhibits.show', $exhibit) }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Back to Exhibit
    </a>
</div>

<form method="POST" action="{{ route('exhibits.update', $exhibit) }}" enctype="multipart/form-data">
    @csrf
    @method('PATCH')
    
    @include('exhibits._form')

    <div class="d-flex justify-content-between mt-4">
        <button type="button" class="btn btn-danger"
                onclick="if(confirm('Are you sure you want to delete this exhibit?')) { document.getElementById('delete-form').submit(); }">
            <i class="bi bi-trash"></i> Delete
        </button>
        
        <div>
            <a href="{{ route('exhibits.show', $exhibit) }}" class="btn btn-outline-secondary me-2">Cancel</a>
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-save"></i> Update Exhibit
            </button>
        </div>
    </div>
</form>

{{-- Separate delete form outside main form --}}
<form id="delete-form" method="POST" action="{{ route('exhibits.destroy', $exhibit) }}" class="d-none">
    @csrf
    @method('DELETE')
</form>
@endsection
