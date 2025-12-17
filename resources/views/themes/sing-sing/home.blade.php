@extends('themes.sing-sing.layouts.app')

@section('content')

    <div class="hero bg-dark" style="height: 35vh; background-image: url('{{ asset('themes/sing-sing/images/bg.png') }}'); background-repeat: no-repeat; background-size: cover; display: flex; flex-direction: column; align-items: center; justify-content: center; color: white;">
        <h1>Sing Sing Prison Museum</h1>
        <p class="lead">Oral Histories Archive</p>
    </div>

<div class="container">


    @php
        try {
            $collectionsCount = \App\Models\Collection::where('visibility', 'public')->count();
            $itemsCount = \App\Models\Item::where('visibility', 'public')->count();
            $exhibitsCount = \App\Models\Exhibit::where('visibility', 'public')->where('status', 'published')->count();
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
                <p>The database needs to be set up. Please contact the site administrator.</p>
            </div>
        </div>
    </div>
    @endif

    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <div class="card h-100 shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">Collections</h5>
                    <p class="card-text">Browse curated collections of historical materials organized by theme and subject.</p>
                    <a href="{{ route('collections.index') }}" class="btn btn-primary">View Collections</a>
                </div>
                <div class="card-footer bg-light">
                    <small class="text-muted">{{ $dbReady ? $collectionsCount : '—' }} collections available</small>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card h-100 shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">Items</h5>
                    <p class="card-text">Search and explore individual artifacts, documents, photographs, and recordings.</p>
                    <a href="{{ route('items.index') }}" class="btn btn-primary">Browse Items</a>
                </div>
                <div class="card-footer bg-light">
                    <small class="text-muted">{{ $dbReady ? $itemsCount : '—' }} items available</small>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card h-100 shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">Exhibits</h5>
                    <p class="card-text">Experience curated narratives and thematic presentations of our collections.</p>
                    <a href="{{ route('exhibits.index') }}" class="btn btn-primary">View Exhibits</a>
                </div>
                <div class="card-footer bg-light">
                    <small class="text-muted">{{ $dbReady ? $exhibitsCount : '—' }} exhibits available</small>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">About This Archive</h5>
                    <p class="card-text">
                        This digital archive preserves and shares the history of Sing Sing Prison through 
                        photographs, documents, oral histories, and artifacts. Our mission is to provide 
                        public access to materials that illuminate the complex history of this institution 
                        and its role in American criminal justice.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
