@forelse($items as $item)
    <tr>
        <td>
            @switch($item->item_type)
                @case('audio') <i class="bi bi-music-note-beamed text-primary"></i> @break
                @case('video') <i class="bi bi-film text-danger"></i> @break
                @case('image') <i class="bi bi-image text-success"></i> @break
                @case('document') <i class="bi bi-file-text text-warning"></i> @break
                @default <i class="bi bi-file-earmark text-secondary"></i>
            @endswitch
            {{ $item->title }}
            @if($item->hasTranscript())
                <i class="bi bi-file-earmark-text text-info ms-1" title="Has transcript"></i>
            @endif
        </td>
        <td><code>{{ $item->slug }}</code></td>
        <td>
            @if($item->collection)
                <a href="{{ route('collections.show', $item->collection) }}">{{ $item->collection->title }}</a>
            @else
                <span class="text-muted">—</span>
            @endif
        </td>
        <td>
            <span class="badge bg-light text-dark border">{{ ucfirst($item->item_type) }}</span>
        </td>
        <td>
            @if($item->published_at)
                <span class="badge bg-success">Published</span>
            @else
                <span class="badge bg-secondary">Draft</span>
            @endif
        </td>
        <td>{{ $item->updated_at->diffForHumans() }}</td>
        <td class="text-end">
            <div class="btn-group btn-group-sm">
                <a href="{{ route('items.show', $item) }}" class="btn btn-outline-primary">View</a>
                <a href="{{ route('items.edit', $item) }}" class="btn btn-outline-secondary">Edit</a>
            </div>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="7" class="text-center text-muted">No items found.</td>
    </tr>
@endforelse
