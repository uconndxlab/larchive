@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col">
            <div class="d-flex justify-content-between align-items-center">
                <h2>Create New Item</h2>
                <a href="{{ route('items.index') }}" class="btn btn-outline-secondary">Back to Items</a>
            </div>
        </div>
    </div>

    <form action="{{ route('items.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        
        <div class="row">
            {{-- Main Content Column --}}
            <div class="col-lg-8 col-xxl-9">
                {{-- Quick Start Guide --}}
                <div class="alert alert-primary mb-4" role="alert">
                    <h6 class="alert-heading mb-2">Quick Start</h6>
                    <ol class="mb-0 ps-3">
                        <li>Fill in the basic details below and create the item</li>
                        <li>Then you'll be able to upload media files (images, audio, video, documents)</li>
                        <li>Set workflow status and publish when ready</li>
                    </ol>
                </div>

                @include('items._form')

                {{-- Bottom Action Bar --}}
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex gap-2 justify-content-between align-items-center">
                            <div class="text-muted small">
                                After creating, you can upload media files on the edit page.
                            </div>
                            <div class="d-flex gap-2">
                                <a href="{{ route('items.index') }}" class="btn btn-outline-secondary">Cancel</a>
                                <button type="submit" class="btn btn-primary">Create Item & Continue</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Sidebar Column --}}
            <div class="col-lg-4 col-xxl-3">
                <div class="sticky-top" style="top: 1rem;">
                    @include('items._tags_taxonomies')
                    @include('items._workflow')
                    

                </div>
            </div>
        </div>
    </form>
</div>
@endsection
