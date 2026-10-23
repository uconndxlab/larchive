<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\Term;
use App\Transcripts\TranscriptSourceFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ItemApiController extends Controller
{
    public function index(Request $request)
    {
        $query = Item::with(['collection', 'terms.taxonomy']);

        if (! $request->user()?->isAdmin()) {
            $query->published()->visibleTo($request->user());
        }

        $items = $query
            ->orderByDesc('published_at')
            ->latest()
            ->get()
            ->map(fn (Item $item) => [
                'id' => $item->id,
                'title' => $item->title,
                'slug' => $item->slug,
                'status' => $item->status,
                'visibility' => $item->visibility,
                'description' => $item->description,
                'published_at' => $item->published_at?->toIso8601String(),
                'collection' => $item->collection ? [
                    'id' => $item->collection->id,
                    'title' => $item->collection->title,
                ] : null,
                'keywords' => $item->tags()->get(['id', 'name', 'slug'])->map(fn ($term) => [
                    'id' => $term->id,
                    'name' => $term->name,
                    'slug' => $term->slug,
                ])->values()->all(),
                'transcript' => $item->hasTranscript() ? [
                    'id' => $item->transcript?->id,
                    'filename' => $item->transcript?->filename,
                    'format' => $item->transcript?->format,
                ] : null,
            ]);

        return response()->json([
            'data' => $items,
            'count' => $items->count(),
        ]);
    }

    public function transcript(Request $request, Item $item)
    {
        $user = $request->user();

        if (! $user?->isAdmin()) {
            $visibleItem = Item::published()->visibleTo($user)->whereKey($item->id)->first();

            if (! $visibleItem) {
                abort(404);
            }
        }

        $transcript = $item->transcript()->first();

        if (! $transcript) {
            return response()->json([
                'message' => 'No transcript found for this item.',
            ], 404);
        }

        $source = TranscriptSourceFactory::create($item);
        $segments = $source?->segments() ?? [];
        $rawText = $transcript->path ? Storage::disk('public')->get($transcript->path) : null;

        return response()->json([
            'item_id' => $item->id,
            'title' => $item->title,
            'filename' => $transcript->filename,
            'format' => $transcript->format ?? 'unknown',
            'url' => $transcript->path ? Storage::disk('public')->url($transcript->path) : null,
            'text' => $source ? $this->flattenSegments($segments) : ($rawText ?: null),
            'segments' => $segments,
        ]);
    }

    public function keywords(Request $request)
    {
        $query = Term::with('taxonomy')
            ->whereHas('taxonomy', fn ($query) => $query->where('key', 'tags'))
            ->orderBy('name');

        if (! $request->user()?->isAdmin()) {
            $query->whereHas('items', fn ($query) => $query
                ->published()
                ->visibleTo($request->user()));
        }

        $keywords = $query->get()->map(function (Term $term) use ($request) {
            $count = $request->user() && ! $request->user()->isAdmin()
                ? $term->items()->published()->visibleTo($request->user())->count()
                : $term->items()->count();

            return [
                'id' => $term->id,
                'name' => $term->name,
                'slug' => $term->slug,
                'taxonomy' => $term->taxonomy?->name,
                'count' => $count,
            ];
        })->values()->all();

        return response()->json([
            'data' => $keywords,
            'count' => count($keywords),
        ]);
    }

    protected function flattenSegments(array $segments): string
    {
        return collect($segments)
            ->pluck('text')
            ->filter(fn ($line) => is_string($line) && trim($line) !== '')
            ->implode("\n");
    }
}
