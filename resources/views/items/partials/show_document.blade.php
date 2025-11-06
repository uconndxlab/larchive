{{-- Document Item Display --}}
@php
    $documentFiles = $item->media->where('is_transcript', false)->filter(function($m) {
        return in_array($m->mime_type, [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'text/plain',
        ]);
    });
@endphp

@if($documentFiles->isNotEmpty())
    <div class="card border-warning mb-4">
        <div class="card-header bg-warning bg-opacity-10">
            <h5 class="mb-0">
                <i class="bi bi-file-earmark-text"></i> Documents
            </h5>
        </div>
        <div class="card-body">
            @foreach($documentFiles as $doc)
                <div class="document-wrapper mb-4 @if(!$loop->last) pb-4 border-bottom @endif">
                    <div class="d-flex align-items-start">
                        <div class="me-3">
                            <div class="text-center p-3 bg-light rounded" style="width: 80px; height: 100px; display: flex; align-items: center; justify-content: center;">
                                @if(str_contains($doc->mime_type, 'pdf'))
                                    <i class="bi bi-file-pdf fs-1 text-danger"></i>
                                @elseif(str_contains($doc->mime_type, 'word'))
                                    <i class="bi bi-file-word fs-1 text-primary"></i>
                                @elseif(str_contains($doc->mime_type, 'excel') || str_contains($doc->mime_type, 'spreadsheet'))
                                    <i class="bi bi-file-excel fs-1 text-success"></i>
                                @else
                                    <i class="bi bi-file-text fs-1 text-secondary"></i>
                                @endif
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <h6>{{ $doc->filename }}</h6>
                            <p class="text-muted mb-2">
                                {{ $doc->mime_type }}<br>
                                {{ number_format($doc->size / 1024, 1) }} KB
                                @if($doc->alt_text)
                                    <br><em>{{ $doc->alt_text }}</em>
                                @endif
                            </p>
                            <div class="btn-group btn-group-sm">
                                <a href="{{ Storage::url($doc->path) }}" 
                                   class="btn btn-primary" 
                                   download>
                                    <i class="bi bi-download"></i> Download
                                </a>
                                @if(str_contains($doc->mime_type, 'pdf'))
                                    <a href="{{ Storage::url($doc->path) }}" 
                                       class="btn btn-outline-primary" 
                                       target="_blank">
                                        <i class="bi bi-eye"></i> View
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- PDF Preview --}}
                    @if(str_contains($doc->mime_type, 'pdf'))
                        <div class="mt-3">
                            <iframe src="{{ Storage::url($doc->path) }}" 
                                    class="w-100 border rounded" 
                                    style="height: 600px;">
                            </iframe>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
@else
    <div class="alert alert-warning">
        <i class="bi bi-exclamation-triangle"></i> No documents attached yet. 
        <a href="{{ route('items.edit', $item) }}">Add media files</a> to display documents.
    </div>
@endif

{{-- Dublin Core Metadata --}}
@if($item->metadata->isNotEmpty())
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="bi bi-tags"></i> Metadata
            </h5>
        </div>
        <div class="card-body">
            <dl class="row mb-0">
                @foreach($item->getDublinCore() as $key => $value)
                    <dt class="col-sm-4 text-muted small">{{ \App\Models\Concerns\DublinCore::getLabel($key) }}</dt>
                    <dd class="col-sm-8">{{ $value }}</dd>
                @endforeach
            </dl>
        </div>
    </div>
@endif
