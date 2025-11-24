@php
    use App\Models\SiteNotice;
    
    // Get the singleton site notice
    $siteNotice = SiteNotice::instance();
    
    // Check if notice should be shown:
    // 1. Notice is enabled and has content
    // 2. User hasn't acknowledged it (check cookie)
    $shouldShow = $siteNotice->shouldShow() && !request()->cookie('larchive_notice_acknowledged');
@endphp

@if($shouldShow)
<!-- Site Notice Modal -->
<div class="modal fade" id="siteNoticeModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="siteNoticeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="siteNoticeModalLabel">{{ $siteNotice->title }}</h5>
            </div>
            <div class="modal-body">
                {!! $siteNotice->body !!}
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="acceptNoticeBtn">I Agree</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Show the modal on page load
    const modalElement = document.getElementById('siteNoticeModal');
    const modal = new bootstrap.Modal(modalElement);
    modal.show();
    
    // Handle acceptance
    document.getElementById('acceptNoticeBtn').addEventListener('click', function() {
        // Send acknowledgment to server
        fetch('{{ route('notice.acknowledge') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Set cookie on client side as well (belt and suspenders)
                document.cookie = 'larchive_notice_acknowledged=true; max-age=31536000; path=/';
                
                // Hide the modal
                modal.hide();
            }
        })
        .catch(error => {
            console.error('Error acknowledging notice:', error);
            // Still hide the modal on error
            modal.hide();
        });
    });
});
</script>
@endif
