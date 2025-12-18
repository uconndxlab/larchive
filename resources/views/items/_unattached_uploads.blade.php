{{-- Unattached Uploads from FTP/SFTP --}}
@php
    $unattachedFiles = $item->getUnattachedUploads();
@endphp

@if(count($unattachedFiles) > 0)
<div class="card mb-4">
    <div class="card-header bg-info text-white">
        <h5 class="mb-0">
            <i class="bi bi-cloud-upload"></i> Unattached Uploads
        </h5>
    </div>
    <div class="card-body">
        <p class="text-muted small mb-3">
            Files uploaded via FTP/SFTP to <code>storage/app/items/{{ $item->id }}/incoming/</code>
        </p>

        <div class="table-responsive">
            <table class="table table-sm table-hover">
                <thead>
                    <tr>
                        <th>Filename</th>
                        <th>Size</th>
                        <th>Modified</th>
                        <th style="width: 350px;">Attach As</th>
                        <th style="width: 100px;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($unattachedFiles as $file)
                    <tr>
                        <td>
                            <code class="small">{{ $file['name'] }}</code>
                        </td>
                        <td class="text-muted small">
                            {{ number_format($file['size'] / 1024, 1) }} KB
                        </td>
                        <td class="text-muted small">
                            {{ date('Y-m-d H:i', $file['modified']) }}
                        </td>
                        <td>
                            <form action="{{ route('items.attach-incoming', $item) }}" 
                                  method="POST" 
                                  class="attach-form"
                                  id="attach-form-{{ md5($file['name']) }}">
                                @csrf
                                <input type="hidden" name="filename" value="{{ $file['name'] }}">
                                
                                <div class="d-flex gap-2 align-items-center">
                                    <select name="attach_as" 
                                            class="form-select form-select-sm attach-type-select" 
                                            style="width: 120px;"
                                            data-form-id="attach-form-{{ md5($file['name']) }}">
                                        <option value="main">Main Media</option>
                                        <option value="supplemental">Supplemental</option>
                                    </select>

                                    {{-- Supplemental metadata fields (hidden by default) --}}
                                    <div class="supplemental-fields d-none">
                                        <input type="text" 
                                               name="label" 
                                               class="form-control form-control-sm" 
                                               placeholder="Label"
                                               style="width: 120px;">
                                    </div>
                                </div>

                                {{-- Expanded supplemental options (shown below when supplemental is selected) --}}
                                <div class="supplemental-expanded mt-2 d-none">
                                    <div class="row g-2">
                                        <div class="col-md-6">
                                            <label class="form-label small">Role</label>
                                            <input type="text" 
                                                   name="role" 
                                                   class="form-control form-control-sm" 
                                                   placeholder="e.g., transcript, notes">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small">Visibility</label>
                                            <select name="visibility" class="form-select form-select-sm">
                                                <option value="public">Public</option>
                                                <option value="authenticated">Authenticated Only</option>
                                                <option value="hidden">Hidden</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </td>
                        <td>
                            <button type="submit" 
                                    form="attach-form-{{ md5($file['name']) }}" 
                                    class="btn btn-sm btn-success">
                                Attach
                            </button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
// Toggle supplemental fields visibility
document.querySelectorAll('.attach-type-select').forEach(select => {
    select.addEventListener('change', function() {
        const formId = this.dataset.formId;
        const form = document.getElementById(formId);
        const supplementalFields = form.querySelector('.supplemental-fields');
        const supplementalExpanded = form.querySelector('.supplemental-expanded');
        
        if (this.value === 'supplemental') {
            supplementalFields.classList.remove('d-none');
            supplementalExpanded.classList.remove('d-none');
        } else {
            supplementalFields.classList.add('d-none');
            supplementalExpanded.classList.add('d-none');
        }
    });
});
</script>
@else
<div class="card mb-4 border-secondary">
    <div class="card-body text-center text-muted">
        <p class="mb-2">
            <i class="bi bi-folder2-open" style="font-size: 2rem;"></i>
        </p>
        <p class="mb-0">
            No unattached uploads found in <code>storage/app/items/{{ $item->id }}/incoming/</code>
        </p>
        <p class="small mb-0 mt-2">
            Upload files via FTP/SFTP and refresh this page to attach them.
        </p>
    </div>
</div>
@endif
