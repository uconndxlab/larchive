@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1>Create Page</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="{{ route('exhibits.index') }}">Exhibits</a>
                </li>
                <li class="breadcrumb-item">
                    <a href="{{ route('exhibits.show', $exhibit) }}">{{ $exhibit->title }}</a>
                </li>
                <li class="breadcrumb-item active">New Page</li>
            </ol>
        </nav>
    </div>
</div>

<form method="POST" action="{{ route('exhibits.pages.store', $exhibit) }}">
    @csrf
    
    <div class="row">
        <div class="col-md-8">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Page Content</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
                        <input type="text" 
                               class="form-control @error('title') is-invalid @enderror" 
                               id="title" 
                               name="title" 
                               value="{{ old('title') }}" 
                               required>
                        @error('title')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="slug" class="form-label">Slug</label>
                        <input type="text" 
                               class="form-control @error('slug') is-invalid @enderror" 
                               id="slug" 
                               name="slug" 
                               value="{{ old('slug') }}"
                               placeholder="auto-generated-from-title">
                        <small class="text-muted">Leave blank to auto-generate from title</small>
                        @error('slug')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="visibility" class="form-label">Visibility <span class="text-danger">*</span></label>
                        <select 
                            class="form-select @error('visibility') is-invalid @enderror" 
                            id="visibility" 
                            name="visibility" 
                            required
                        >
                            <option value="public" {{ old('visibility') == 'public' ? 'selected' : '' }}>
                                Public - Visible to everyone
                            </option>
                            <option value="authenticated" {{ old('visibility', 'authenticated') == 'authenticated' ? 'selected' : '' }}>
                                Authenticated - Requires login
                            </option>
                            <option value="hidden" {{ old('visibility') == 'hidden' ? 'selected' : '' }}>
                                Hidden - Admin only
                            </option>
                        </select>
                        <small class="text-muted">Control who can view this page</small>
                        @error('visibility')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="content" class="form-label">Content</label>
                        <textarea class="form-control @error('content') is-invalid @enderror" 
                                  id="content" 
                                  name="content" 
                                  rows="12">{{ old('content') }}</textarea>
                        @error('content')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Page Settings</h5>
                </div>
                <div class="card-body">
                    @if(isset($parent))
                        <input type="hidden" name="parent_id" value="{{ $parent->id }}">
                        <div class="alert alert-info mb-3">
                            <i class="bi bi-info-circle"></i>
                            This will be a sub-page of <strong>{{ $parent->title }}</strong>
                        </div>
                    @else
                        <div class="mb-3">
                            <label for="parent_id" class="form-label">Parent Page</label>
                            <select class="form-select @error('parent_id') is-invalid @enderror" 
                                    id="parent_id" 
                                    name="parent_id">
                                <option value="">None (Top Level)</option>
                                @foreach($exhibit->topLevelPages as $topPage)
                                    <option value="{{ $topPage->id }}" {{ old('parent_id') == $topPage->id ? 'selected' : '' }}>
                                        {{ $topPage->title }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted">Create a section under an existing page</small>
                            @error('parent_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    @endif
                </div>
            </div>

            @include('partials._taxonomy_selector', ['resource' => null])

            <div class="alert alert-light border">
                <h6 class="alert-heading">
                    <i class="bi bi-lightbulb"></i>
                    Tip
                </h6>
                <p class="small mb-0">
                    After creating the page, you'll be able to attach items and customize the layout.
                </p>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-between mt-4">
        <a href="{{ route('exhibits.show', $exhibit) }}" class="btn btn-outline-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-save"></i> Create Page
        </button>
    </div>
</form>
@endsection
