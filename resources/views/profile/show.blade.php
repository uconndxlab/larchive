@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-10">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>My Profile</h1>
            <a href="{{ route('profile.edit') }}" class="btn btn-primary">Edit Profile</a>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="row">
            {{-- Profile Information --}}
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Profile Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="text-muted small">Name</label>
                            <p class="mb-0"><strong>{{ $user->name }}</strong></p>
                        </div>
                        <div class="mb-3">
                            <label class="text-muted small">Email</label>
                            <p class="mb-0">{{ $user->email }}</p>
                        </div>
                        <div class="mb-3">
                            <label class="text-muted small">Role</label>
                            <p class="mb-0">
                                @if($user->role === 'admin')
                                    <span class="badge bg-danger">Admin</span>
                                @elseif($user->role === 'curator')
                                    <span class="badge bg-primary">Curator</span>
                                @else
                                    <span class="badge bg-secondary">Contributor</span>
                                @endif
                            </p>
                        </div>
                        <div class="mb-3">
                            <label class="text-muted small">Member Since</label>
                            <p class="mb-0">{{ $user->created_at->format('M d, Y') }}</p>
                        </div>
                        <div class="mb-0">
                            <label class="text-muted small">Last Login</label>
                            <p class="mb-0">
                                @if($user->last_login_at)
                                    {{ $user->last_login_at->format('M d, Y g:i A') }}
                                    <span class="text-muted">({{ $user->last_login_at->diffForHumans() }})</span>
                                @else
                                    <span class="text-muted">Never</span>
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Activity Statistics --}}
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Activity</h5>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-6 mb-3">
                                <div class="p-3 bg-light rounded">
                                    <h3 class="mb-1">{{ $user->items_count }}</h3>
                                    <small class="text-muted">Items Created</small>
                                </div>
                            </div>
                            <div class="col-6 mb-3">
                                <div class="p-3 bg-light rounded">
                                    <h3 class="mb-1">{{ $user->media_count }}</h3>
                                    <small class="text-muted">Files Uploaded</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-3 bg-light rounded">
                                    <h3 class="mb-1">{{ $user->collections_count }}</h3>
                                    <small class="text-muted">Collections</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-3 bg-light rounded">
                                    <h3 class="mb-1">{{ $user->exhibits_count }}</h3>
                                    <small class="text-muted">Exhibits</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Recent Activity --}}
        @if($user->items_count > 0)
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Recent Items</h5>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        @foreach($user->items()->latest()->limit(5)->get() as $item)
                            <a href="{{ route('items.show', $item) }}" class="list-group-item list-group-item-action">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong>{{ $item->title }}</strong>
                                        @if($item->collection)
                                            <br><small class="text-muted">{{ $item->collection->title }}</small>
                                        @endif
                                    </div>
                                    <small class="text-muted">{{ $item->created_at->diffForHumans() }}</small>
                                </div>
                            </a>
                        @endforeach
                    </div>
                    @if($user->items_count > 5)
                        <div class="mt-3">
                            <a href="{{ route('items.index') }}" class="btn btn-sm btn-outline-secondary">View All Items</a>
                        </div>
                    @endif
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
