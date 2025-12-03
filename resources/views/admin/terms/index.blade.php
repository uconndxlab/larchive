@extends('layouts.app')

@section('content')
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.taxonomies.index') }}">Taxonomies</a></li>
        <li class="breadcrumb-item active">{{ $taxonomy->name }}</li>
    </ol>
</nav>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1>{{ $taxonomy->name }} Terms</h1>
        @if($taxonomy->description)
            <p class="text-muted">{{ $taxonomy->description }}</p>
        @endif
    </div>
    <a href="{{ route('admin.terms.create', $taxonomy) }}" class="btn btn-primary">Add Term</a>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
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
                        <th>Slug</th>
                        @if($taxonomy->hierarchical)
                            <th>Parent</th>
                        @endif
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($terms as $term)
                        <tr>
                            <td>
                                <strong>{{ $term->name }}</strong>
                                @if($term->description)
                                    <br><small class="text-muted">{{ Str::limit($term->description, 80) }}</small>
                                @endif
                            </td>
                            <td><code>{{ $term->slug }}</code></td>
                            @if($taxonomy->hierarchical)
                                <td>
                                    @if($term->parent)
                                        <span class="badge bg-secondary">{{ $term->parent->name }}</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                            @endif
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('terms.show', [$taxonomy, $term]) }}" class="btn btn-outline-info" title="View" target="_blank">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="{{ route('admin.terms.edit', [$taxonomy, $term]) }}" class="btn btn-outline-secondary" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form action="{{ route('admin.terms.destroy', [$taxonomy, $term]) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this term?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger" title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $taxonomy->hierarchical ? 4 : 3 }}" class="text-center text-muted py-4">
                                No terms found. <a href="{{ route('admin.terms.create', $taxonomy) }}">Add one</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
