@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Taxonomies</h1>
    <a href="{{ route('admin.taxonomies.create') }}" class="btn btn-primary">Create Taxonomy</a>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Key</th>
                        <th>Terms</th>
                        <th>Hierarchical</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($taxonomies as $taxonomy)
                        <tr>
                            <td>
                                <strong>{{ $taxonomy->name }}</strong>
                                @if($taxonomy->description)
                                    <br><small class="text-muted">{{ Str::limit($taxonomy->description, 60) }}</small>
                                @endif
                            </td>
                            <td><code>{{ $taxonomy->key }}</code></td>
                            <td>
                                <a href="{{ route('admin.terms.index', $taxonomy) }}" class="text-decoration-none">
                                    {{ $taxonomy->terms_count }} term{{ $taxonomy->terms_count !== 1 ? 's' : '' }}
                                </a>
                            </td>
                            <td>
                                @if($taxonomy->hierarchical)
                                    <span class="badge bg-info">Yes</span>
                                @else
                                    <span class="badge bg-secondary">No</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('admin.terms.index', $taxonomy) }}" class="btn btn-outline-primary" title="Manage Terms">
                                        <i class="bi bi-tags"></i> Terms
                                    </a>
                                    <a href="{{ route('admin.taxonomies.edit', $taxonomy) }}" class="btn btn-outline-secondary" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    @if($taxonomy->key !== 'tags')
                                        <form action="{{ route('admin.taxonomies.destroy', $taxonomy) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure? This will also delete all terms in this taxonomy.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger" title="Delete">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">
                                No taxonomies found. <a href="{{ route('admin.taxonomies.create') }}">Create one</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
