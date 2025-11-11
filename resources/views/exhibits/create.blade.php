@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Create Exhibit</h1>
    <a href="{{ route('exhibits.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Back to Exhibits
    </a>
</div>

<form method="POST" action="{{ route('exhibits.store') }}" enctype="multipart/form-data">
    @csrf
    
    @include('exhibits._form')

    <div class="d-flex justify-content-between mt-4">
        <a href="{{ route('exhibits.index') }}" class="btn btn-outline-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-save"></i> Create Exhibit
        </button>
    </div>
</form>
@endsection
