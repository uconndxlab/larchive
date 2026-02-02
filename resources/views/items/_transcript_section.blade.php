@if(in_array($item->item_type ?? 'other', ['audio', 'video']))
    <div class="mb-4 pb-4 border-bottom">
        <h6 class="mb-3">Transcript</h6>
        
        @if(isset($item->id) && $item->transcript)
            @php
                $transcriptSource = \App\Transcripts\TranscriptSourceFactory::create($item);
                $vttPath = $transcriptSource?->getVttPath();
                $hasVttGenerated = $vttPath && $item->transcript->format !== 'vtt';
                $isBinaryFormat = in_array($item->transcript->format, ['pdf', 'doc', 'docx']);
            @endphp
            
            <div class="alert alert-success mb-3">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        Current transcript: <strong>{{ $item->transcript->filename }}</strong>
                        @if($item->transcript->format)
                            <span class="badge bg-secondary">{{ strtoupper($item->transcript->format) }}</span>
                        @endif
                        ({{ number_format($item->transcript->size / 1024, 1) }} KB)
                        
                        @if($isBinaryFormat)
                            <div class="mt-2 small text-muted">
                                <i class="bi bi-info-circle"></i>
                                Note: {{ strtoupper($item->transcript->format) }} files cannot be automatically converted to WebVTT format for video player captions.
                            </div>
                        @elseif($hasVttGenerated)
                            <div class="mt-2 small">
                                <i class="bi bi-file-earmark-text"></i>
                                <strong>WebVTT version available:</strong>
                                <a href="{{ route('vtt.serve', ['path' => $vttPath]) }}" 
                                   class="btn btn-sm btn-outline-primary ms-2" 
                                   download="{{ pathinfo($item->transcript->filename, PATHINFO_FILENAME) }}.vtt">
                                    <i class="bi bi-download"></i> Download WebVTT
                                </a>
                                <span class="text-muted ms-2">(Auto-generated for video player compatibility)</span>
                            </div>
                        @endif
                    </div>
                    
                    <button type="button" class="btn btn-sm btn-outline-danger" 
                            onclick="if(confirm('Remove this transcript?')) { document.getElementById('remove-transcript').value = '1'; document.getElementById('item-edit-form').submit(); }">
                        Remove
                    </button>
                </div>
            </div>
        @endif

        <div class="row">
            <div class="col-md-8">
                <label for="transcript" class="form-label">
                    @if(isset($item->id) && $item->transcript)
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
                    accept=".xml,.txt,.vtt,.srt,.pdf,.doc,.docx"
                    form="item-edit-form"
                >
                <div class="form-text">
                    Transcript of the audio or video content. Accepted formats: OHMS XML, TXT, VTT, SRT, PDF, DOC, DOCX (max 10MB)
                    @if(isset($item->id) && $item->transcript)
                        <br>Uploading a new file will replace the existing transcript.
                    @endif
                </div>
                @error('transcript')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-4">
                <label for="transcript_format" class="form-label">Transcript Format</label>
                <select class="form-select" id="transcript_format" name="transcript_format" form="item-edit-form">
                    <option value="">Auto-detect from file</option>
                    <option value="ohms">OHMS XML</option>
                    <option value="vtt">WebVTT (VTT)</option>
                    <option value="srt">SRT</option>
                    <option value="txt">Plain Text</option>
                </select>
                <div class="form-text">Leave blank to auto-detect from file extension.</div>
            </div>
        </div>

        <input type="hidden" id="remove-transcript" name="remove_transcript" value="0" form="item-edit-form">
    </div>
@endif
