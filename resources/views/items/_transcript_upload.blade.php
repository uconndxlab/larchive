@if(in_array($itemType, ['audio', 'video']))
<div class="card border-info mb-3">
    <div class="card-header bg-info bg-opacity-10">
        <h6 class="mb-0">
            <i class="bi bi-file-text"></i> Transcript File (Optional)
        </h6>
    </div>
    <div class="card-body">
        @if(isset($item) && $item->hasTranscript())
            <div class="alert alert-success mb-3">
                <i class="bi bi-check-circle"></i> 
                Current transcript: <strong>{{ $item->transcript->filename }}</strong>
                ({{ number_format($item->transcript->size / 1024, 1) }} KB)
            </div>
        @endif

        <div class="mb-2">
            <label for="transcript" class="form-label">Upload Transcript</label>
            <input 
                type="file" 
                class="form-control @error('transcript') is-invalid @enderror" 
                id="transcript" 
                name="transcript"
                accept=".txt,.vtt,.srt,.pdf,.doc,.docx"
            >
            <div class="form-text">
                Accepted formats: TXT, VTT, SRT, PDF, DOC, DOCX (max 10MB)
                @if(isset($item) && $item->hasTranscript())
                    <br><strong>Note:</strong> Uploading a new file will replace the existing transcript.
                @endif
            </div>
            @error('transcript')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>
@endif
