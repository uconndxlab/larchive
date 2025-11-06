<div id="media-list">
    @if($item->media->isEmpty())
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i> No media files attached yet.
        </div>
    @else
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th style="width: 80px;">Preview</th>
                        <th>Filename</th>
                        <th style="width: 100px;">Size</th>
                        <th>Alt Text</th>
                        <th style="width: 120px;" class="text-center">Order</th>
                        <th style="width: 80px;" class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody id="media-rows">
                    @foreach($item->media as $media)
                        <tr data-media-id="{{ $media->id }}">
                            <td>
                                @if(str_starts_with($media->mime_type, 'image/'))
                                    <img src="{{ Storage::url($media->path) }}" 
                                         alt="{{ $media->alt_text }}" 
                                         class="img-thumbnail" 
                                         style="max-width: 60px; max-height: 60px; object-fit: cover;">
                                @else
                                    <div class="text-center p-2 bg-light rounded" style="width: 60px; height: 60px; display: flex; align-items: center; justify-content: center;">
                                        <strong class="text-muted small">
                                            {{ strtoupper(pathinfo($media->filename, PATHINFO_EXTENSION)) }}
                                        </strong>
                                    </div>
                                @endif
                            </td>
                            <td>
                                <small class="text-muted d-block">{{ $media->mime_type }}</small>
                                {{ $media->filename }}
                            </td>
                            <td>
                                <span class="badge bg-secondary">
                                    {{ number_format($media->size / 1024, 1) }} KB
                                </span>
                            </td>
                            <td>
                                <form hx-patch="/media/{{ $media->id }}" 
                                      hx-target="closest tr" 
                                      hx-swap="outerHTML"
                                      class="d-flex gap-1">
                                    @csrf
                                    <input type="text" 
                                           name="alt_text" 
                                           value="{{ $media->alt_text }}" 
                                           class="form-control form-control-sm" 
                                           placeholder="Add alt text...">
                                    <button type="submit" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-check"></i>
                                    </button>
                                </form>
                            </td>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm" role="group">
                                    <button type="button" 
                                            class="btn btn-outline-secondary btn-sm move-up"
                                            @if($loop->first) disabled @endif>
                                        <i class="bi bi-arrow-up"></i>
                                    </button>
                                    <button type="button" 
                                            class="btn btn-outline-secondary btn-sm move-down"
                                            @if($loop->last) disabled @endif>
                                        <i class="bi bi-arrow-down"></i>
                                    </button>
                                </div>
                            </td>
                            <td class="text-center">
                                <button type="button"
                                        class="btn btn-sm btn-outline-danger"
                                        hx-delete="/media/{{ $media->id }}"
                                        hx-target="#media-list"
                                        hx-swap="outerHTML"
                                        hx-confirm="Delete this media file?">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Reorder Form --}}
        <form id="reorder-form" 
              hx-patch="/items/{{ $item->id }}/media/reorder" 
              hx-target="#media-list"
              hx-swap="outerHTML">
            @csrf
            <input type="hidden" name="order" id="media-order" value="">
            <button type="submit" class="btn btn-sm btn-secondary" id="save-order-btn" style="display: none;">
                <i class="bi bi-save"></i> Save Order
            </button>
        </form>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                setupMediaReordering();
            });

            // Re-setup after HTMX swaps
            document.body.addEventListener('htmx:afterSwap', function(evt) {
                if (evt.detail.target.id === 'media-list') {
                    setupMediaReordering();
                }
            });

            function setupMediaReordering() {
                const rows = document.querySelectorAll('#media-rows tr');
                const saveBtn = document.getElementById('save-order-btn');
                const orderInput = document.getElementById('media-order');

                rows.forEach((row, index) => {
                    const upBtn = row.querySelector('.move-up');
                    const downBtn = row.querySelector('.move-down');

                    if (upBtn) {
                        upBtn.onclick = () => moveRow(row, -1);
                    }
                    if (downBtn) {
                        downBtn.onclick = () => moveRow(row, 1);
                    }
                });

                function moveRow(row, direction) {
                    const tbody = row.parentElement;
                    const currentIndex = Array.from(tbody.children).indexOf(row);
                    const targetIndex = currentIndex + direction;

                    if (targetIndex < 0 || targetIndex >= tbody.children.length) return;

                    if (direction === -1) {
                        tbody.insertBefore(row, tbody.children[targetIndex]);
                    } else {
                        tbody.insertBefore(row, tbody.children[targetIndex + 1]);
                    }

                    updateButtons();
                    updateOrderInput();
                    saveBtn.style.display = 'inline-block';
                }

                function updateButtons() {
                    const allRows = tbody.querySelectorAll('tr');
                    allRows.forEach((r, idx) => {
                        const up = r.querySelector('.move-up');
                        const down = r.querySelector('.move-down');
                        if (up) up.disabled = (idx === 0);
                        if (down) down.disabled = (idx === allRows.length - 1);
                    });
                }

                function updateOrderInput() {
                    const tbody = document.getElementById('media-rows');
                    const mediaIds = Array.from(tbody.querySelectorAll('tr'))
                        .map(row => row.dataset.mediaId);
                    orderInput.value = JSON.stringify(mediaIds);
                }
            }
        </script>
    @endif
</div>
