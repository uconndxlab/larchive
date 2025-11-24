@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Site Notice Settings</h5>
            </div>
            <div class="card-body">
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                <form action="{{ route('admin.site-notice.update') }}" method="POST">
                    @csrf
                    @method('PUT')
                    
                    <div class="mb-3 form-check form-switch">
                        <input type="checkbox" class="form-check-input" id="enabled" name="enabled" value="1" 
                            {{ old('enabled', $notice->enabled) ? 'checked' : '' }}>
                        <label class="form-check-label" for="enabled">
                            <strong>Enable Site Notice</strong>
                            <small class="text-muted d-block">When enabled, the notice will be shown to all visitors who haven't acknowledged it.</small>
                        </label>
                    </div>

                    <div class="mb-3">
                        <label for="title" class="form-label">Title</label>
                        <input type="text" 
                            class="form-control @error('title') is-invalid @enderror" 
                            id="title" 
                            name="title" 
                            value="{{ old('title', $notice->title) }}"
                            placeholder="e.g., Welcome to Larchive">
                        @error('title')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="body" class="form-label">Body (HTML)</label>
                        <textarea 
                            class="form-control @error('body') is-invalid @enderror" 
                            id="body" 
                            name="body" 
                            rows="8"
                            placeholder="Enter your notice text here. You can use HTML tags like &lt;p&gt;, &lt;strong&gt;, &lt;a&gt;, etc.">{{ old('body', $notice->body) }}</textarea>
                        <small class="form-text text-muted">
                            HTML is supported. Common tags: &lt;p&gt;, &lt;strong&gt;, &lt;em&gt;, &lt;a href=""&gt;, &lt;ul&gt;, &lt;li&gt;
                        </small>
                        @error('body')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h6 class="card-title">Preview</h6>
                                <div id="preview">
                                    @if($notice->title)
                                        <h5>{{ $notice->title }}</h5>
                                    @endif
                                    @if($notice->body)
                                        <div>{!! $notice->body !!}</div>
                                    @else
                                        <p class="text-muted">No content to preview yet.</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">Information</h6>
            </div>
            <div class="card-body">
                <p class="small mb-2"><strong>How it works:</strong></p>
                <ul class="small">
                    <li>When enabled, the notice appears as a modal to all visitors</li>
                    <li>Once a user clicks "I Agree", they won't see it again for 1 year</li>
                    <li>Acceptance is tracked via browser cookie</li>
                    <li>Clearing cookies will show the notice again</li>
                </ul>
                
                @if($notice->updated_at)
                    <p class="small text-muted mb-0">
                        Last updated: {{ $notice->updated_at->format('M d, Y g:i A') }}
                    </p>
                @endif
            </div>
        </div>
    </div>
</div>

<script>
// Simple live preview
document.getElementById('body')?.addEventListener('input', function(e) {
    const preview = document.getElementById('preview');
    const title = document.getElementById('title').value;
    const body = e.target.value;
    
    let html = '';
    if (title) {
        html += '<h5>' + title.replace(/</g, '&lt;').replace(/>/g, '&gt;') + '</h5>';
    }
    if (body) {
        html += '<div>' + body + '</div>';
    } else {
        html = '<p class="text-muted">No content to preview yet.</p>';
    }
    
    preview.innerHTML = html;
});

document.getElementById('title')?.addEventListener('input', function(e) {
    const preview = document.getElementById('preview');
    const title = e.target.value;
    const body = document.getElementById('body').value;
    
    let html = '';
    if (title) {
        html += '<h5>' + title.replace(/</g, '&lt;').replace(/>/g, '&gt;') + '</h5>';
    }
    if (body) {
        html += '<div>' + body + '</div>';
    } else {
        html = '<p class="text-muted">No content to preview yet.</p>';
    }
    
    preview.innerHTML = html;
});
</script>
@endsection
