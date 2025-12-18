@if(in_array($itemType, ['audio', 'video']))
    @if(isset($item) && $item->hasTranscript())
        <div class="alert alert-success mb-3">
            Current transcript: <strong>{{ $item->transcript->filename }}</strong>
            ({{ number_format($item->transcript->size / 1024, 1) }} KB)
        </div>
    @endif

    <div class="mb-3">
        <label for="transcript" class="form-label">
            @if(isset($item) && $item->hasTranscript())
                Replace Transcript File
            @else
                Upload Transcript File (Optional)
            @endif
        </label>
        <input 
            type="file" 
            class="form-control @error('transcript') is-invalid @enderror" 
            id="transcript" 
            name="transcript"
            accept=".txt,.vtt,.srt,.pdf,.doc,.docx"
        >
        <div class="form-text">
            Transcript of the audio or video content. Accepted formats: TXT, VTT, SRT, PDF, DOC, DOCX (max 10MB)
            @if(isset($item) && $item->hasTranscript())
                <br>Uploading a new file will replace the existing transcript.
            @endif
        </div>
        @error('transcript')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
@else
    <p class="text-muted mb-0">
        Transcript upload is only available for audio and video items.
    </p>
@endif
