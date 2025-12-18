{{-- Workflow & Save Actions --}}
<div class="card mb-3">
    <div class="card-header">
        <h6 class="mb-0">Workflow</h6>
    </div>
    <div class="card-body">
        <div class="mb-3">
            <label for="status" class="form-label">
                Status <span class="text-danger">*</span>
            </label>
            <select 
                class="form-select @error('status') is-invalid @enderror" 
                id="status" 
                name="status" 
                required
                @if(isset($item)) form="item-edit-form" @endif
            >
                @if(Auth::user()->isContributor() && !Auth::user()->isCurator())
                    {{-- Contributors can only use draft and in_review --}}
                    <option value="draft" {{ old('status', $item->status ?? 'draft') == 'draft' ? 'selected' : '' }}>
                        Draft
                    </option>
                    <option value="in_review" {{ old('status', $item->status ?? 'draft') == 'in_review' ? 'selected' : '' }}>
                        In Review
                    </option>
                @else
                    {{-- Curators and admins have full access --}}
                    <option value="draft" {{ old('status', $item->status ?? 'draft') == 'draft' ? 'selected' : '' }}>
                        Draft
                    </option>
                    <option value="in_review" {{ old('status', $item->status ?? 'draft') == 'in_review' ? 'selected' : '' }}>
                        In Review
                    </option>
                    <option value="published" {{ old('status', $item->status ?? 'draft') == 'published' ? 'selected' : '' }}>
                        Published
                    </option>
                    <option value="archived" {{ old('status', $item->status ?? 'draft') == 'archived' ? 'selected' : '' }}>
                        Archived
                    </option>
                @endif
            </select>
            <div class="form-text small">
                <strong>Draft:</strong> Work in progress<br>
                <strong>In Review:</strong> Ready for curator approval
                @if(Auth::user()->isCurator())
                    <br><strong>Published:</strong> Live and visible per visibility setting
                    <br><strong>Archived:</strong> Hidden from public listings
                @endif
            </div>
            @error('status')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-0">
            <label for="visibility" class="form-label">
                Visibility <span class="text-danger">*</span>
            </label>
            <select 
                class="form-select @error('visibility') is-invalid @enderror" 
                id="visibility" 
                name="visibility" 
                required
                @if(isset($item)) form="item-edit-form" @endif
            >
                <option value="public" {{ old('visibility', $item->visibility ?? 'authenticated') == 'public' ? 'selected' : '' }}>
                    Public
                </option>
                <option value="authenticated" {{ old('visibility', $item->visibility ?? 'authenticated') == 'authenticated' ? 'selected' : '' }}>
                    Authenticated
                </option>
                <option value="hidden" {{ old('visibility', $item->visibility ?? 'authenticated') == 'hidden' ? 'selected' : '' }}>
                    Hidden
                </option>
            </select>
            <div class="form-text small">
                <strong>Public:</strong> Anyone can view<br>
                <strong>Authenticated:</strong> Login required<br>
                <strong>Hidden:</strong> Admins/curators only
            </div>
            @error('visibility')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>


