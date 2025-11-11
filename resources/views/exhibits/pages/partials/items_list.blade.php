<div class="card-body" id="items-list">
    @if($page->items->count() > 0)
        <div class="list-group">
            @foreach($page->items as $item)
                <div class="list-group-item">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <h6 class="mb-1">{{ $item->title }}</h6>
                            <div class="small text-muted mb-2">
                                Layout: <span class="badge bg-secondary">{{ $item->pivot->layout_position }}</span>
                            </div>
                            @if($item->pivot->caption)
                                <p class="small mb-0">{{ $item->pivot->caption }}</p>
                            @endif
                        </div>
                        <form method="POST" 
                              action="{{ route('exhibits.pages.items.detach', [$page->exhibit, $page, $item]) }}"
                              hx-post="{{ route('exhibits.pages.items.detach', [$page->exhibit, $page, $item]) }}"
                              hx-target="#items-list"
                              hx-swap="outerHTML"
                              onsubmit="return confirm('Remove this item from the page?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                <i class="bi bi-x"></i>
                            </button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <p class="text-muted mb-0">
            <i class="bi bi-info-circle"></i>
            No items attached yet. Click "Attach Item" to add items to this page.
        </p>
    @endif
</div>
