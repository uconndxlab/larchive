@forelse($collections as $collection)
    <tr>
        <td>{{ $collection->title }}</td>
        <td><code>{{ $collection->slug }}</code></td>
        <td>
            @if($collection->published_at)
                <span class="badge bg-success">Published</span>
            @else
                <span class="badge bg-secondary">Draft</span>
            @endif
        </td>
        <td>{{ $collection->updated_at->diffForHumans() }}</td>
        <td class="text-end">
            <div class="btn-group btn-group-sm" role="group">
                <a href="{{ route('collections.show', $collection) }}" class="btn btn-outline-primary">View</a>
                <a href="{{ route('collections.edit', $collection) }}" class="btn btn-outline-secondary">Edit</a>
            </div>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="5" class="text-center text-muted">No collections found.</td>
    </tr>
@endforelse
