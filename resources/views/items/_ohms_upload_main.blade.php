{{-- OHMS XML Upload (only for audio/video items) --}}
@if(isset($item) && in_array($item->item_type, ['audio', 'video']))
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">
            @if(empty($item->ohms_json))
                OHMS Indexing (Optional)
            @else
                OHMS Indexing
            @endif
        </h5>
    </div>
    <div class="card-body">
        @if(empty($item->ohms_json))
            <p class="text-muted small mb-3">
                Upload an OHMS XML file to enable indexed segment navigation and enhanced oral history features for this {{ $item->item_type }} item.
            </p>
        @else
            <div class="alert alert-success mb-3" role="alert">
                OHMS data loaded: {{ count($item->ohms_json['segments'] ?? []) }} segments indexed
            </div>
        @endif

        <form action="{{ route('items.ohms.store', $item) }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="row align-items-end">
                <div class="col-md-9">
                    <label for="ohms_xml" class="form-label">
                        @if(empty($item->ohms_json))
                            Select OHMS XML File
                        @else
                            Replace OHMS XML File
                        @endif
                    </label>
                    <input 
                        type="file" 
                        class="form-control @error('ohms_xml') is-invalid @enderror" 
                        id="ohms_xml"
                        name="ohms_xml" 
                        accept=".xml,.txt"
                    >
                    @error('ohms_xml')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary w-100">
                        @if(empty($item->ohms_json))
                            Upload
                        @else
                            Replace
                        @endif
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endif
