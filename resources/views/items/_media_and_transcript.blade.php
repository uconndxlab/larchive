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
            Upload media files for this item. Drag and drop files or click to browse. Large files are automatically split into chunks for reliable uploads.
        </p>

        {{-- Dropzone for Large File Uploads --}}
        <div id="large-file-dropzone" class="dropzone mb-4"></div>
        
        {{-- Legacy upload form (hidden, kept for HTMX compatibility) --}}
        <div class="d-none">
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
                </div>
                
                <div class="col-md-3">
                    <button type="submit" form="media-upload-form" class="btn btn-primary w-100">Upload Files</button>
                </div>
            </div>
        </div>

        <link rel="stylesheet" href="https://unpkg.com/dropzone@5/dist/min/dropzone.min.css" type="text/css" />
        <script src="https://unpkg.com/dropzone@5/dist/min/dropzone.min.js"></script>

        <style>
        .dropzone {
            border: 2px dashed #0087F7;
            border-radius: 8px;
            background: #f8f9fa;
            min-height: 200px;
            padding: 20px;
            transition: all 0.3s ease;
        }

        .dropzone:hover {
            border-color: #0056b3;
            background: #e9ecef;
        }

        .dropzone .dz-message {
            font-size: 1.1rem;
            color: #666;
            margin: 2rem 0;
        }

        .dropzone .dz-message .note {
            font-size: 0.875rem;
            color: #999;
            display: block;
            margin-top: 0.5rem;
        }

        .dropzone.dz-drag-hover {
            border-color: #28a745;
            background: #d4edda;
        }

        .dropzone .dz-preview {
            margin: 10px;
        }

        .dropzone .dz-preview .dz-progress {
            height: 8px;
        }
        </style>

        <script>
            Dropzone.autoDiscover = false;

            document.addEventListener('DOMContentLoaded', function() {
                const itemId = {{ $item->id }};
                const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
                
                const myDropzone = new Dropzone("#large-file-dropzone", {
                    url: `/items/${itemId}/media/chunk`,
                    paramName: "file",
                    chunking: true,
                    forceChunking: true,
                    chunkSize: 2000000, // 2MB chunks
                    parallelChunkUploads: false,
                    retryChunks: true,
                    retryChunksLimit: 3,
                    maxFilesize: 50000, // 50GB max
                    timeout: 300000, // 5 minutes per chunk
                    parallelUploads: 1,
                    uploadMultiple: false,
                    
                    dictDefaultMessage: "Drop files here or click to upload<br><span class='note'>Supports files up to 50GB • Automatic chunked upload for large files</span>",
                    
                    headers: {
                        'X-CSRF-TOKEN': csrfToken
                    },
                    
                    params: function(files, xhr, chunk) {
                        if (chunk) {
                            return {
                                dzuuid: chunk.file.upload.uuid,
                                dzchunkindex: chunk.index,
                                dztotalfilesize: chunk.file.size,
                                dztotalchunkcount: chunk.file.upload.totalChunkCount,
                                dzchunksize: this.options.chunkSize,
                                original_filename: chunk.file.name
                            };
                        }
                    },
                    
                    init: function() {
                        this.on("success", function(file, response) {
                            console.log("Upload complete:", file.name);
                            
                            // Show success message
                            file.previewElement.querySelector('.dz-success-mark').style.opacity = '1';
                            
                            // Reload media list after a brief delay
                            setTimeout(() => {
                                const mediaList = document.getElementById('media-list');
                                if (mediaList) {
                                    // Trigger HTMX to reload the media list
                                    htmx.ajax('GET', `/items/${itemId}/media`, {
                                        target: '#media-list',
                                        swap: 'outerHTML'
                                    });
                                }
                            }, 1000);
                        });
                        
                        this.on("error", function(file, errorMessage, xhr) {
                            console.error("Upload failed:", errorMessage);
                            
                            // Show error in preview
                            if (typeof errorMessage === 'object') {
                                file.previewElement.querySelector('.dz-error-message span').textContent = 
                                    errorMessage.error || 'Upload failed';
                            } else {
                                file.previewElement.querySelector('.dz-error-message span').textContent = errorMessage;
                            }
                        });
                        
                        this.on("uploadprogress", function(file, progress, bytesSent) {
                            console.log("Progress:", file.name, Math.round(progress) + "%");
                        });
                        
                        this.on("sending", function(file, xhr, formData) {
                            console.log("Starting upload:", file.name);
                        });
                        
                        this.on("complete", function(file) {
                            // Remove file from dropzone after 3 seconds on success
                            if (file.status === Dropzone.SUCCESS) {
                                setTimeout(() => {
                                    this.removeFile(file);
                                }, 3000);
                            }
                        });
                    }
                });
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
