@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-md-12">
        <h1 class="mb-4">Larchive Dashboard</h1>
        <p class="lead text-muted mb-5">A minimal digital archive built with Laravel 12, Bootstrap 5, and HTMX.</p>
    </div>
</div>

@php
    try {
        $collectionsCount = \App\Models\Collection::count();
        $itemsCount = \App\Models\Item::count();
        $mediaCount = \App\Models\Media::count();
        $dbReady = true;
    } catch (\Exception $e) {
        $dbReady = false;
    }
@endphp

@if(!$dbReady)
<div class="row">
    <div class="col-md-12">
        <div class="alert alert-warning" role="alert">
            <h5 class="alert-heading">Database Not Initialized</h5>
            <p>It looks like the database tables haven't been created yet. Run the following commands to get started:</p>
            <hr>
            <pre class="mb-0"><code>php artisan migrate
php artisan storage:link</code></pre>
        </div>
    </div>
</div>
@endif

<div class="row g-4">
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body">
                <h5 class="card-title">
                    <i class="bi bi-collection"></i> Collections
                </h5>
                <p class="card-text text-muted">
                    Organize items into thematic groups with metadata and publishing controls.
                </p>
                <a href="{{ route('collections.index') }}" class="btn btn-primary btn-sm">Browse Collections</a>
                <a href="{{ route('collections.create') }}" class="btn btn-outline-secondary btn-sm">Create New</a>
            </div>
            <div class="card-footer bg-light">
                <small class="text-muted">{{ $dbReady ? $collectionsCount : '—' }} total</small>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body">
                <h5 class="card-title">
                    Items
                </h5>
                <p class="card-text text-muted">
                    Core archival units with flexible metadata, media attachments, and collection assignment.
                </p>
                <a href="{{ route('items.index') }}" class="btn btn-primary btn-sm">Browse Items</a>
                <a href="{{ route('items.create') }}" class="btn btn-outline-secondary btn-sm">Create New</a>
            </div>
            <div class="card-footer bg-light">
                <small class="text-muted">{{ $dbReady ? $itemsCount : '—' }} total</small>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body">
                <h5 class="card-title">
                   Media
                </h5>
                <p class="card-text text-muted">
                    Upload and manage images, documents, audio, and video files attached to items.
                </p>
                <span class="badge bg-secondary">Managed per item</span>
            </div>
            <div class="card-footer bg-light">
                <small class="text-muted">{{ $dbReady ? $mediaCount : '—' }} files</small>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Features</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>Implemented</h6>
                        <ul class="list-unstyled">
                            <li>✓ Collections CRUD with soft deletes</li>
                            <li>✓ Items CRUD with optional collection assignment</li>
                            <li>✓ Media uploads with MIME validation</li>
                            <li>✓ Flexible key-value metadata</li>
                            <li>✓ HTMX-powered live search & filters</li>
                            <li>✓ Auto-slug generation</li>
                            <li>✓ Publishing workflow (published_at)</li>
                            <li>✓ Bootstrap 5 + HTMX via CDN</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h6>🚧 Roadmap</h6>
                        <ul class="list-unstyled text-muted">
                            <li>• Exhibits (curated item showcases)</li>
                            <li>• Media management UI</li>
                            <li>• Metadata management UI</li>
                            <li>• Drag-and-drop media reordering</li>
                            <li>• Public-facing views</li>
                            <li>• Search across metadata</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-12">
        <div class="card border-info">
            <div class="card-body">
                <h6 class="card-title text-info">Quick Start</h6>
                <ol class="mb-0 small">
                    <li>Run <code>php artisan migrate</code> to create database tables</li>
                    <li>Run <code>php artisan storage:link</code> to enable file uploads</li>
                    <li>Create your first <a href="{{ route('collections.create') }}">Collection</a></li>
                    <li>Add <a href="{{ route('items.create') }}">Items</a> to your collection</li>
                    <li>Upload media files and add metadata to items</li>
                </ol>
            </div>
        </div>
    </div>
</div>
@endsection
