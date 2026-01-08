{{-- User Tracking Card --}}
@if($model->exists)
<div class="card mt-3">
    <div class="card-header bg-light">
        <h6 class="mb-0">
            <i class="bi bi-clock-history"></i> Activity Log
        </h6>
    </div>
    <div class="card-body">
        <dl class="row mb-0 small">
            <dt class="col-sm-4 text-muted">Created</dt>
            <dd class="col-sm-8">
                {{ $model->created_at->format('M j, Y g:i A') }}
                @if($model->creator)
                    <br>
                    <span class="text-muted">by</span> 
                    <strong>{{ $model->creator->name }}</strong>
                    <span class="text-muted">({{ $model->creator->email }})</span>
                @endif
            </dd>

            <dt class="col-sm-4 text-muted">Last Updated</dt>
            <dd class="col-sm-8 mb-0">
                {{ $model->updated_at->format('M j, Y g:i A') }}
                @if($model->updater)
                    <br>
                    <span class="text-muted">by</span> 
                    <strong>{{ $model->updater->name }}</strong>
                    <span class="text-muted">({{ $model->updater->email }})</span>
                @endif
            </dd>
        </dl>
    </div>
</div>
@endif
