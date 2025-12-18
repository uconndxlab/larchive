{{-- Standalone form for media uploads (outside parent form) --}}
<form hx-post="/items/{{ $item->id }}/media" 
      hx-target="#media-list" 
      hx-swap="outerHTML"
      hx-encoding="multipart/form-data"
      hx-indicator=".upload-progress"
      id="media-upload-form"
      style="display: none;">
    @csrf
</form>

{{-- Media Files & Transcript Section --}}
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">Media Files & Transcript</h5>
    </div>
    <div class="card-body">
        {{-- Transcript Section Container (updated dynamically via HTMX) --}}
        <div id="transcript-container">
            @include('items._transcript_section')
        </div>

        {{-- Media Files Section --}}
        <h6 class="mb-3">Media Files</h6>
        <p class="text-muted small mb-3">
            Upload media files for this item (images, audio, video, PDFs, documents). You can designate one as the featured/main file after uploading.
        </p>

        {{-- Error display container --}}
        <div id="upload-errors" class="alert alert-danger d-none mb-3"></div>
        
        {{-- Show validation errors if any --}}
        @if($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        
        <div class="row g-3 align-items-end mb-3">
            <div class="col-md-9">
                <label for="media-files" class="form-label">Select Files</label>
                <input type="file" 
                       class="form-control" 
                       id="media-files" 
                       name="files[]"
                       form="media-upload-form"
                       multiple 
                       required>
                <small class="form-text text-muted">
                    Max 512MB each. Supported: images, PDFs, audio, video, documents.
                </small>
            </div>
            
            <div class="col-md-3">
                <button type="submit" form="media-upload-form" class="btn btn-primary w-100">Upload Files</button>
            </div>
        </div>

        {{-- Upload progress indicator --}}
        <div class="upload-progress htmx-indicator mb-3">
            <div class="progress">
                <div class="progress-bar progress-bar-striped progress-bar-animated" 
                     role="progressbar" 
                     style="width: 100%">
                    Uploading...
                </div>
            </div>
        </div>

        <script>
            // Handle HTMX validation errors (422 responses)
            document.getElementById('media-upload-form')?.addEventListener('htmx:responseError', function(evt) {
                if (evt.detail.xhr.status === 422) {
                    const response = JSON.parse(evt.detail.xhr.responseText);
                    const errorDiv = document.getElementById('upload-errors');
                    const errorMessages = Object.values(response.errors).flat();
                    
                    errorDiv.innerHTML = '<ul class="mb-0">' + 
                        errorMessages.map(msg => '<li>' + msg + '</li>').join('') + 
                        '</ul>';
                    errorDiv.classList.remove('d-none');
                    
                    // Scroll to errors
                    errorDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                }
            });

            // Clear errors on successful upload
            document.getElementById('media-upload-form')?.addEventListener('htmx:afterSwap', function() {
                const errorDiv = document.getElementById('upload-errors');
                errorDiv?.classList.add('d-none');
            });
        </script>

        <hr>

        {{-- Media List Container - loaded via HTMX --}}
        <div id="media-list" 
             hx-get="/items/{{ $item->id }}/media" 
             hx-trigger="load" 
             hx-swap="outerHTML">
            <div class="text-center py-4">
                <div class="spinner-border text-secondary" role="status">
                    <span class="visually-hidden">Loading media...</span>
                </div>
            </div>
        </div>
    </div>
</div>
