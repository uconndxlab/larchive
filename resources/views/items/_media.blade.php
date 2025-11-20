<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="bi bi-file-earmark-image"></i> Media Files
        </h5>
    </div>
    <div class="card-body">
        {{-- Upload Form --}}
        <form hx-post="/items/{{ $item->id }}/media" 
              hx-target="#media-list" 
              hx-swap="outerHTML"
              hx-encoding="multipart/form-data"
              hx-indicator=".upload-progress"
              class="mb-4"
              id="media-upload-form">
            @csrf
            
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
            
            <div class="row g-3">
                <div class="col-md-8">
                    <label for="files" class="form-label">Upload Files</label>
                    <input type="file" 
                           class="form-control" 
                           id="files" 
                           name="files[]" 
                           multiple 
                           required>
                    <small class="form-text text-muted">
                        Select one or more files. Max 512MB each. Supported: images, PDFs, audio, video, documents.
                    </small>
                </div>
                
                <div class="col-md-4">
                    <label for="alt_text" class="form-label">Alt Text (optional)</label>
                    <input type="text" 
                           class="form-control" 
                           id="alt_text" 
                           name="alt_text" 
                           placeholder="Applied to all uploaded files...">
                    <small class="form-text text-muted">
                        You can edit individually after upload.
                    </small>
                </div>
            </div>

            <div class="mt-3">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-cloud-upload"></i> Upload Files
                </button>
            </div>

            {{-- Upload progress indicator --}}
            <div class="upload-progress htmx-indicator mt-3">
                <div class="progress">
                    <div class="progress-bar progress-bar-striped progress-bar-animated" 
                         role="progressbar" 
                         style="width: 100%">
                        Uploading...
                    </div>
                </div>
            </div>
        </form>

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
        <div hx-get="/items/{{ $item->id }}/media" 
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
