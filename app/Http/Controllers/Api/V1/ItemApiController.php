<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\Media;
use App\Models\Term;
use App\Services\TranscriptStorage;
use Illuminate\Http\Request;

class ItemApiController extends Controller
{
    public function index(Request $request)
    {
        $query = Item::with(['collection', 'terms.taxonomy', 'transcript']);

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
                'transcript' => ($item->transcript && $item->transcript->item_id === $item->id
                    && $this->mediaVisible($item->transcript, $request)) ? [
                        'id' => $item->transcript?->id,
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

        abort_unless($transcript->item_id === $item->id && $this->mediaVisible($transcript, $request), 404);
        // All recordings belonging to this item are returned; no arbitrary primary
        // recording is inferred for interviews with multiple tracks or versions.
        $recordings = $item->media()->orderBy('id')->get()
            ->filter(fn (Media $media) => ! TranscriptStorage::isTranscript($media)
                && (str_starts_with($media->mime_type, 'audio/') || str_starts_with($media->mime_type, 'video/'))
                && $this->mediaVisible($media, $request))
            ->map(fn (Media $media) => [
                'media_id' => $media->id,
                'mime_type' => $media->mime_type,
                'duration_seconds' => $media->duration_seconds,
            ])->values();

        return response()->json([
            'item_id' => $item->id,
            'media_id' => $transcript->id,
            'title' => $item->title,
            'format' => $transcript->format ?? strtolower(pathinfo($transcript->filename, PATHINFO_EXTENSION)),
            'transcript_text' => $transcript->transcript_text,
            'text' => $transcript->transcript_text,
            'segments' => $transcript->transcript_segments ?? [],
            'recordings' => $recordings,
        ], 200, ['Cache-Control' => 'private, no-store'], JSON_INVALID_UTF8_SUBSTITUTE);
    }

    private function mediaVisible(Media $media, Request $request): bool
    {
        $visibility = $media->metadata['visibility'] ?? 'public';

        return $request->user()?->isAdmin()
            || $visibility === 'public'
            || ($visibility === 'authenticated' && $request->user() !== null);
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
}
