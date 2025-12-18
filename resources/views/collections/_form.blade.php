{{-- Basic Information --}}
<div class="card mb-4">
    <div class="card-header bg-primary bg-opacity-10">
        <h6 class="mb-0">📝 Basic Information</h6>
    </div>
    <div class="card-body">
        <div class="mb-3">
            <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
            <input 
                type="text" 
                class="form-control @error('title') is-invalid @enderror" 
                id="title" 
                name="title" 
                value="{{ old('title', $collection->title ?? '') }}" 
                required
            >
            @error('title')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label for="slug" class="form-label">URL Slug</label>
            <input 
                type="text" 
                class="form-control @error('slug') is-invalid @enderror" 
                id="slug" 
                name="slug" 
                value="{{ old('slug', $collection->slug ?? '') }}"
            >
            <div class="form-text">Leave blank to auto-generate from title.</div>
            @error('slug')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-0">
            <label for="description" class="form-label">Description</label>
            <textarea 
                class="form-control @error('description') is-invalid @enderror" 
                id="description" 
                name="description" 
                rows="4"
            >{{ old('description', $collection->description ?? '') }}</textarea>
            <div class="form-text">Brief summary or overview of this collection.</div>
            @error('description')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>

@include('items._media')

{{-- Tags & Categories --}}
<div class="card my-4">
    <div class="card-header bg-warning bg-opacity-10">
        <h6 class="mb-0">🏷️ Tags & Categories</h6>
    </div>
    <div class="card-body">
        @include('partials._taxonomy_selector', ['resource' => $collection ?? null])
    </div>
</div>

{{-- Workflow & Visibility --}}
<div class="card mb-4">
    <div class="card-header bg-success bg-opacity-10">
        <h6 class="mb-0">⚙️ Workflow & Visibility</h6>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="status" class="form-label">
                    Workflow Status <span class="text-danger">*</span>
                </label>
                <select 
                    class="form-select @error('status') is-invalid @enderror" 
                    id="status" 
                    name="status" 
                    required
                >
                    @if(Auth::user()->isContributor() && !Auth::user()->isCurator())
                        <option value="draft" {{ old('status', $collection->status ?? 'draft') == 'draft' ? 'selected' : '' }}>
                            Draft
                        </option>
                        <option value="in_review" {{ old('status', $collection->status ?? 'draft') == 'in_review' ? 'selected' : '' }}>
                            In Review
                        </option>
                    @else
                        <option value="draft" {{ old('status', $collection->status ?? 'draft') == 'draft' ? 'selected' : '' }}>
                            Draft
                        </option>
                        <option value="in_review" {{ old('status', $collection->status ?? 'draft') == 'in_review' ? 'selected' : '' }}>
                            In Review
                        </option>
                        <option value="published" {{ old('status', $collection->status ?? 'draft') == 'published' ? 'selected' : '' }}>
                            Published
                        </option>
                        <option value="archived" {{ old('status', $collection->status ?? 'draft') == 'archived' ? 'selected' : '' }}>
                            Archived
                        </option>
                    @endif
                </select>
                <div class="form-text">
                    <strong>Draft:</strong> Work in progress<br>
                    <strong>In Review:</strong> Ready for curator approval
                    @if(Auth::user()->isCurator())
                        <br><strong>Published:</strong> Live and visible per visibility setting<br>
                        <strong>Archived:</strong> Hidden from public listings
                    @endif
                </div>
                @error('status')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-6 mb-3">
                <label for="visibility" class="form-label">
                    Visibility <span class="text-danger">*</span>
                </label>
                <select 
                    class="form-select @error('visibility') is-invalid @enderror" 
                    id="visibility" 
                    name="visibility" 
                    required
                >
                    <option value="public" {{ old('visibility', $collection->visibility ?? 'authenticated') == 'public' ? 'selected' : '' }}>
                        Public
                    </option>
                    <option value="authenticated" {{ old('visibility', $collection->visibility ?? 'authenticated') == 'authenticated' ? 'selected' : '' }}>
                        Authenticated
                    </option>
                    <option value="hidden" {{ old('visibility', $collection->visibility ?? 'authenticated') == 'hidden' ? 'selected' : '' }}>
                        Hidden
                    </option>
                </select>
                <div class="form-text">
                    <strong>Public:</strong> Anyone can view<br>
                    <strong>Authenticated:</strong> Login required<br>
                    <strong>Hidden:</strong> Admins and curators only
                </div>
                @error('visibility')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="alert alert-info small mb-0">
            <i class="bi bi-info-circle"></i>
            <strong>Note:</strong> Collections must be set to "Published" status <em>and</em> have appropriate visibility to appear on the public site.
        </div>
    </div>
</div>

