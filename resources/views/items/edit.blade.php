@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2>Edit Item: {{ $item->title }}</h2>
                    <div class="mt-2">
                        <span
                            class="badge bg-{{ $item->status === 'published' ? 'success' : ($item->status === 'draft' ? 'secondary' : 'warning') }} me-2">
                            {{ ucfirst($item->status) }}
                        </span>
                        <span
                            class="badge bg-{{ $item->visibility === 'public' ? 'info' : ($item->visibility === 'authenticated' ? 'primary' : 'dark') }}">
                            {{ ucfirst($item->visibility) }}
                        </span>
                    </div>
                </div>
                <div class="btn-group">
                    <a href="{{ route('items.show', $item) }}" class="btn btn-outline-secondary">View Item</a>
                    <a href="{{ route('items.index') }}" class="btn btn-outline-secondary">Back to List</a>
                </div>
            </div>
        </div>
    </div>



    {{-- Two-column layout with separate forms --}}
    <div class="row">
        {{-- Main Content Column with Tabs --}}
        <div class="col-lg-8 col-xxl-9">
            {{-- Nav Tabs --}}
            <ul class="nav nav-tabs mb-3" id="itemEditTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="details-tab" data-bs-toggle="tab" data-bs-target="#details"
                        type="button" role="tab" aria-controls="details" aria-selected="true">
                        Item Details
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="media-tab" data-bs-toggle="tab" data-bs-target="#media" type="button"
                        role="tab" aria-controls="media" aria-selected="false">
                        Media & Transcripts
                    </button>
                </li>
            </ul>

            {{-- Tab Content --}}
            <div class="tab-content" id="itemEditTabsContent">
                {{-- Item Details Tab --}}
                <div class="tab-pane fade show active" id="details" role="tabpanel" aria-labelledby="details-tab">
                    {{-- Main Item Edit Form --}}
                    <form id="item-edit-form" action="{{ route('items.update', $item) }}" method="POST"
                        enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        @include('items._form')

                        {{-- OHMS Upload (only for audio/video) --}}
                        @include('items._ohms_upload_main')

                        {{-- Bottom Action Bar
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex gap-2 justify-content-end">
                                    <a href="{{ route('items.show', $item) }}"
                                        class="btn btn-outline-secondary">Cancel</a>
                                    <button type="submit" form="item-edit-form" class="btn btn-primary">Save
                                        Changes</button>
                                </div>
                            </div>
                        </div> --}}
                    </form>
                </div>

                {{-- Media & Transcripts Tab --}}
                <div class="tab-pane fade" id="media" role="tabpanel" aria-labelledby="media-tab">

                    @include('items._unattached_uploads')

                    @include('items._media_and_transcript')
                </div>
            </div>
        </div>

        {{-- Sidebar Column --}}
        <div class="col-lg-4 col-xxl-3">
            <div class="sticky-top" style="top: 1rem;">
                {{-- Save Actions --}}
                <div class="card">
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            @if(isset($item))
                            <button type="submit" form="item-edit-form" class="btn btn-primary">
                                Save Changes
                            </button>
                            <a href="{{ route('items.show', $item) }}" class="btn btn-outline-secondary">
                                Cancel
                            </a>
                            @else
                            <button type="submit" class="btn btn-primary">
                                Create Item
                            </button>
                            <a href="{{ route('items.index') }}" class="btn btn-outline-secondary">
                                Cancel
                            </a>
                            @endif
                        </div>

                        <div class="alert alert-info small mt-3 mb-0">
                            Items must be <strong>Published</strong> and have appropriate <strong>Visibility</strong> to
                            appear on the public site.
                        </div>
                    </div>
                </div>
                @include('items._featured_image')

                @include('items._tags_taxonomies')

                @include('items._workflow')

                {{-- Danger Zone --}}
                <div class="card border-danger mt-3">
                    <div class="card-header bg-danger text-white">
                        <h6 class="mb-0">Danger Zone</h6>
                    </div>
                    <div class="card-body">
                        <p class="card-text small">Deleting this item will also delete all associated media and
                            metadata.</p>
                        <form action="{{ route('items.destroy', $item) }}" method="POST"
                            onsubmit="return confirm('Are you sure you want to delete this item and all its media/metadata?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm w-100">Delete Item</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection