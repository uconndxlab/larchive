@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>{{ $item->title }}</h1>
    <div class="btn-group">
        <a href="{{ route('items.edit', $item) }}" class="btn btn-primary">Edit</a>
        <a href="{{ route('items.index') }}" class="btn btn-outline-secondary">Back to List</a>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">
                    @switch($item->item_type)
                        @case('audio')  @break
                        @case('video')  @break
                        @case('image')  @break
                        @case('document')  @break
                        @default 📦
                    @endswitch
                    Details
                </h5>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-3">Type</dt>
                    <dd class="col-sm-9">
                        <span class="badge bg-secondary">{{ ucfirst($item->item_type) }}</span>
                    </dd>

                    <dt class="col-sm-3">Slug</dt>
                    <dd class="col-sm-9"><code>{{ $item->slug }}</code></dd>

                    <dt class="col-sm-3">Collection</dt>
                    <dd class="col-sm-9">
                        @if($item->collection)
                            <a href="{{ route('collections.show', $item->collection) }}">{{ $item->collection->title }}</a>
                        @else
                            <span class="text-muted">None</span>
                        @endif
                    </dd>

                    <dt class="col-sm-3">Status</dt>
                    <dd class="col-sm-9">
                        @if($item->published_at)
                            <span class="badge bg-success">Published</span>
                            <small class="text-muted ms-2">{{ $item->published_at->format('M d, Y') }}</small>
                        @else
                            <span class="badge bg-secondary">Draft</span>
                        @endif
                    </dd>

                    @if($item->description)
                        <dt class="col-sm-3">Description</dt>
                        <dd class="col-sm-9">{{ $item->description }}</dd>
                    @endif

                    <dt class="col-sm-3">Created</dt>
                    <dd class="col-sm-9">{{ $item->created_at->format('M d, Y g:i A') }}</dd>

                    <dt class="col-sm-3">Updated</dt>
                    <dd class="col-sm-9">{{ $item->updated_at->format('M d, Y g:i A') }}</dd>
                </dl>
            </div>
        </div>

        {{-- Type-specific content rendering --}}
        @switch($item->item_type)
            @case('audio')
                @include('items.partials.show_audio')
                @break
            @case('video')
                @include('items.partials.show_video')
                @break
            @case('image')
                @include('items.partials.show_image')
                @break
            @case('document')
                @include('items.partials.show_document')
                @break
            @default
                @include('items.partials.show_other')
        @endswitch
    </div>
</div>
@endsection
