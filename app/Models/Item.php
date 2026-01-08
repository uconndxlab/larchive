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
        'featured_image_id',
        'item_type',
        'transcript_id',
        'ohms_json',
        'title',
        'slug',
        'visibility',
        'status',
        'description',
        'published_at',
        'extra',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'extra' => 'array',
        'ohms_json' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($item) {
            if (auth()->check()) {
                $item->created_by = auth()->id();
                $item->updated_by = auth()->id();
            }
        });

        static::updating(function ($item) {
            if (auth()->check()) {
                $item->updated_by = auth()->id();
            }
        });
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

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

    /**
     * Featured image (points to a Media record).
     * Used as the primary visual representation of the item.
     */
    public function featuredImage()
    {
        return $this->belongsTo(Media::class, 'featured_image_id');
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

    /**
     * Scope items by status.
     */
    public function scopeWithStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to only published items.
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }

    /**
     * Get list of unattached uploads from the incoming directory.
     * Returns array of file info: ['name' => filename, 'size' => bytes, 'modified' => timestamp]
     */
    public function getUnattachedUploads(): array
    {
        $incomingPath = storage_path("app/public/items/{$this->id}/incoming");
        
        if (!is_dir($incomingPath)) {
            return [];
        }

        // Get all attached file paths for this item
        $attachedPaths = $this->media()->pluck('path')->toArray();
        
        $files = [];
        $items = scandir($incomingPath);
        
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            
            $fullPath = $incomingPath . '/' . $item;
            
            if (!is_file($fullPath)) {
                continue;
            }
            
            // Check if this file is already attached
            $relativePath = "items/{$this->id}/incoming/{$item}";
            if (in_array($relativePath, $attachedPaths)) {
                continue;
            }
            
            $files[] = [
                'name' => $item,
                'size' => filesize($fullPath),
                'modified' => filemtime($fullPath),
            ];
        }
        
        return $files;
    }
}
