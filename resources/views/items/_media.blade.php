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
              enctype="multipart/form-data"
              class="mb-4">
            @csrf
            
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
            <div class="htmx-indicator mt-3">
                <div class="progress">
                    <div class="progress-bar progress-bar-striped progress-bar-animated" 
                         role="progressbar" 
                         style="width: 100%">
                        Uploading...
                    </div>
                </div>
            </div>
        </form>

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
