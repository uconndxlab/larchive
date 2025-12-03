<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;

/**
 * Item model for archival objects.
 * 
 * Supports oral history use cases with:
 * - Item types (audio, video, image, document, other)
 * - Transcript relationship for audio/video items
 * - Flexible Dublin Core metadata via key-value metadata table
 */
class Item extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'collection_id',
        'item_type',
        'transcript_id',
        'ohms_json',
        'title',
        'slug',
        'visibility',
        'description',
        'published_at',
        'extra',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'extra' => 'array',
        'ohms_json' => 'array',
    ];

    public function collection()
    {
        return $this->belongsTo(Collection::class);
    }

    public function media()
    {
        return $this->hasMany(Media::class)->orderBy('sort_order');
    }

    public function regularMedia()
    {
        return $this->hasMany(Media::class)->where('is_transcript', false)->orderBy('sort_order');
    }

    public function metadata()
    {
        return $this->hasMany(Metadata::class);
    }

    public function exhibits()
    {
        return $this->belongsToMany(Exhibit::class)
            ->withPivot('sort_order', 'caption')
            ->withTimestamps()
            ->orderByPivot('sort_order');
    }

    /**
     * Transcript file (points to a Media record).
     * Typically a .txt, .vtt, .srt, or .pdf file.
     */
    public function transcript()
    {
        return $this->belongsTo(Media::class, 'transcript_id');
    }

    public function terms()
    {
        return $this->morphToMany(Term::class, 'termable')
            ->withTimestamps()
            ->with('taxonomy');
    }

    public function tags()
    {
        return $this->terms()->whereHas('taxonomy', fn ($q) => $q->where('key', 'tags'));
    }

    // --- Dublin Core Helpers ---

    /**
     * Get a Dublin Core metadata value by key.
     * Example: $item->getDC('dc.creator')
     */
    public function getDC(string $key): ?string
    {
        return $this->metadata()->where('key', $key)->value('value');
    }

    /**
     * Set a Dublin Core metadata value.
     * Example: $item->setDC('dc.creator', 'Jane Doe')
     */
    public function setDC(string $key, ?string $value): void
    {
        if ($value === null) {
            $this->metadata()->where('key', $key)->delete();
            return;
        }

        $this->metadata()->updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );
    }

    /**
     * Get all Dublin Core metadata as an associative array.
     * Only returns keys that start with 'dc.'
     */
    public function getDublinCore(): array
    {
        return $this->metadata()
            ->where('key', 'like', 'dc.%')
            ->pluck('value', 'key')
            ->toArray();
    }

    // --- Type Checks ---

    public function isAudio(): bool
    {
        return $this->item_type === 'audio';
    }

    public function isVideo(): bool
    {
        return $this->item_type === 'video';
    }

    public function isImage(): bool
    {
        return $this->item_type === 'image';
    }

    public function isDocument(): bool
    {
        return $this->item_type === 'document';
    }

    public function hasTranscript(): bool
    {
        return $this->transcript_id !== null;
    }

    /**
     * Scope items visible to a given user.
     */
    public function scopeVisibleTo(Builder $query, ?User $user): Builder
    {
        // Admins see everything
        if ($user && $user->isAdmin()) {
            return $query;
        }

        // Authenticated users see public and authenticated
        if ($user) {
            return $query->whereIn('visibility', ['public', 'authenticated']);
        }

        // Guests see only public
        return $query->where('visibility', 'public');
    }
}
