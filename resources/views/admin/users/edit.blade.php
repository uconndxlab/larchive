@extends('admin.layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Edit User: {{ $user->name }}</h5>
                <a href="{{ route('admin.users.index') }}" class="btn btn-sm btn-outline-secondary">Back</a>
            </div>
            <div class="card-body">
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @if(session('api_token'))
                    <div class="alert alert-warning" role="alert">
                        <h6 class="alert-heading">New API token created for {{ session('api_token_name', 'this user') }}</h6>
                        <div class="mt-2">
                            <code class="d-block text-break">{{ session('api_token') }}</code>
                        </div>
                        <div class="small mt-2">Copy this token now. It will not be shown again.</div>
                    </div>
                @endif

                {{-- User Activity Summary --}}
                <div class="card bg-light mb-4">
                    <div class="card-body">
                        <h6 class="card-title">User Activity</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <p class="mb-1"><strong>Member Since:</strong> {{ $user->created_at->format('M d, Y') }}</p>
                                <p class="mb-1"><strong>Last Login:</strong> 
                                    @if($user->last_login_at)
                                        {{ $user->last_login_at->format('M d, Y g:i A') }}
                                        <span class="text-muted">({{ $user->last_login_at->diffForHumans() }})</span>
                                    @else
                                        <span class="text-muted">Never</span>
                                    @endif
                                </p>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-1"><strong>Items Created:</strong> {{ $user->items_count }}</p>
                                <p class="mb-1"><strong>Files Uploaded:</strong> {{ $user->media_count }}</p>
                                <p class="mb-1"><strong>Collections:</strong> {{ $user->collections_count }}</p>
                                <p class="mb-1"><strong>Exhibits:</strong> {{ $user->exhibits_count }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card bg-light mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">API Tokens</h6>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('admin.users.tokens.store', $user) }}" method="POST" class="mb-3">
                            @csrf
                            <label for="token_name" class="form-label">Create API Token</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="token_name" name="name" placeholder="e.g. External importer" required>
                                <button type="submit" class="btn btn-primary">Create</button>
                            </div>
                        </form>

                        @php($tokens = $user->tokens()->latest()->get())
                        @if($tokens->isEmpty())
                            <p class="text-muted mb-0">No API tokens exist for this user yet.</p>
                        @else
                            <div class="list-group list-group-flush">
                                @foreach($tokens as $token)
                                    <div class="list-group-item px-0 d-flex justify-content-between align-items-center gap-3">
                                        <div>
                                            <div class="fw-semibold">{{ $token->name ?: 'Unnamed token' }}</div>
                                            <small class="text-muted">
                                                Created {{ $token->created_at?->format('M d, Y g:i A') ?? 'unknown' }}
                                                @if($token->last_used_at)
                                                    · Last used {{ $token->last_used_at->format('M d, Y g:i A') }}
                                                @endif
                                            </small>
                                        </div>
                                        <form action="{{ route('admin.users.tokens.destroy', [$user, $token]) }}" method="POST" onsubmit="return confirm('Revoke this API token?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Revoke</button>
                                        </form>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>

                <form action="{{ route('admin.users.update', $user) }}" method="POST">
                    @csrf
                    @method('PUT')
                    
                    <div class="mb-3">
                        <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                        <input 
                            type="text" 
                            class="form-control @error('name') is-invalid @enderror" 
                            id="name" 
                            name="name" 
                            value="{{ old('name', $user->name) }}" 
                            required
                        >
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                        <input 
                            type="email" 
                            class="form-control @error('email') is-invalid @enderror" 
                            id="email" 
                            name="email" 
                            value="{{ old('email', $user->email) }}" 
                            required
                        >
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="role" class="form-label">Role <span class="text-danger">*</span></label>
                        <select 
                            class="form-select @error('role') is-invalid @enderror" 
                            id="role" 
                            name="role" 
                            required
                        >
                            <option value="contributor" {{ old('role', $user->role) === 'contributor' ? 'selected' : '' }}>Contributor</option>
                            <option value="curator" {{ old('role', $user->role) === 'curator' ? 'selected' : '' }}>Curator</option>
                            <option value="admin" {{ old('role', $user->role) === 'admin' ? 'selected' : '' }}>Admin</option>
                        </select>
                        @error('role')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <hr class="my-4">

                    <div class="mb-3">
                        <label for="password" class="form-label">New Password</label>
                        <input 
                            type="password" 
                            class="form-control @error('password') is-invalid @enderror" 
                            id="password" 
                            name="password"
                        >
                        <div class="form-text">Leave blank to keep current password. Minimum 8 characters if changing.</div>
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="password_confirmation" class="form-label">Confirm New Password</label>
                        <input 
                            type="password" 
                            class="form-control @error('password_confirmation') is-invalid @enderror" 
                            id="password_confirmation" 
                            name="password_confirmation"
                        >
                        @error('password_confirmation')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Update User</button>
                        <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
