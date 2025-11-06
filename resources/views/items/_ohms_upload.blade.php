<div class="card mb-4">
    <div class="card-header">
        <h6 class="mb-0">
            <i class="bi bi-file-earmark-code"></i>
            @if(empty($item->ohms_json))
                Upload OHMS XML
            @else
                Replace OHMS XML
            @endif
        </h6>
    </div>
    <div class="card-body">
        @if(empty($item->ohms_json))
            <p class="card-text small text-muted">
                Upload an OHMS XML file to enable indexed segment navigation and enhanced oral history features.
            </p>
        @else
            <div class="alert alert-success small mb-3" role="alert">
                <i class="bi bi-check-circle"></i>
                OHMS data loaded: {{ count($item->ohms_json['segments'] ?? []) }} segments
            </div>
        @endif

        <form action="{{ route('items.ohms.store', $item) }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="mb-3">
                <input 
                    type="file" 
                    class="form-control form-control-sm @error('ohms_xml') is-invalid @enderror" 
                    name="ohms_xml" 
                    accept=".xml,.txt"
                    required
                >
                @error('ohms_xml')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <button type="submit" class="btn btn-primary btn-sm w-100">
                @if(empty($item->ohms_json))
                    <i class="bi bi-upload"></i> Upload OHMS XML
                @else
                    <i class="bi bi-arrow-repeat"></i> Replace OHMS XML
                @endif
            </button>
        </form>
    </div>
</div>
