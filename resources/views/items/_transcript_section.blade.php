@if(in_array($item->item_type ?? 'other', ['audio', 'video']))
    <div class="mb-4 pb-4 border-bottom">
        <h6 class="mb-3">Transcript</h6>
        
        @if(isset($item->id) && method_exists($item, 'hasTranscript') && $item->hasTranscript())
            <div class="alert alert-success mb-3">
                Current transcript: <strong>{{ $item->transcript->filename }}</strong>
                ({{ number_format($item->transcript->size / 1024, 1) }} KB)
            </div>
        @endif

        <div class="mb-0">
            <label for="transcript" class="form-label">
                @if(isset($item->id) && method_exists($item, 'hasTranscript') && $item->hasTranscript())
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
                form="item-edit-form"
            >
            <div class="form-text">
                Transcript of the audio or video content. Accepted formats: TXT, VTT, SRT, PDF, DOC, DOCX (max 10MB)
                @if(isset($item->id) && method_exists($item, 'hasTranscript') && $item->hasTranscript())
                    <br>Uploading a new file will replace the existing transcript.
                @endif
            </div>
            @error('transcript')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
@endif
