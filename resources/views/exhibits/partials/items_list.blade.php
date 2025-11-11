<div id="exhibit-items-list">
    @if($exhibit->items->count() > 0)
        <div class="list-group">
            @foreach($exhibit->items as $item)
                <div class="list-group-item">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <h6 class="mb-1">
                                <a href="{{ route('items.show', $item) }}">{{ $item->title }}</a>
                            </h6>
                            @if($item->pivot->caption)
                                <p class="small text-muted mb-0">{{ $item->pivot->caption }}</p>
                            @endif
                        </div>
                        <form method="POST" 
                              action="{{ route('exhibits.items.detach', [$exhibit, $item]) }}"
                              hx-delete="{{ route('exhibits.items.detach', [$exhibit, $item]) }}"
                              hx-target="#exhibit-items-list"
                              hx-swap="outerHTML"
                              onsubmit="return confirm('Remove this item from the exhibit?');">
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
            No items attached to this exhibit yet.
        </p>
    @endif
</div>
