<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use App\Models\User;

class Exhibit extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title',
        'slug',
        'visibility',
        'status',
        'description',
        'credits',
        'theme',
        'cover_image',
        'featured',
        'sort_order',
        'published_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'featured' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();
        
        // Auto-generate slug from title if not provided
        static::creating(function ($exhibit) {
            if (auth()->check()) {
                $exhibit->created_by = auth()->id();
                $exhibit->updated_by = auth()->id();
            }
            
            if (empty($exhibit->slug)) {
                $slug = Str::slug($exhibit->title);
                $originalSlug = $slug;
                $counter = 1;
                
                // Ensure slug is unique
                while (static::where('slug', $slug)->exists()) {
                    $slug = $originalSlug . '-' . $counter;
                    $counter++;
                }
                
                $exhibit->slug = $slug;
            }
        });

        static::updating(function ($exhibit) {
            if (auth()->check()) {
                $exhibit->updated_by = auth()->id();
            }
        });
    }

    // Relationships
    
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function items()
    {
        return $this->belongsToMany(Item::class)
            ->withPivot('sort_order', 'caption')
            ->withTimestamps()
            ->orderByPivot('sort_order');
    }

    public function pages()
    {
        return $this->hasMany(ExhibitPage::class)->orderBy('sort_order');
    }

    public function topLevelPages()
    {
        return $this->hasMany(ExhibitPage::class)
            ->whereNull('parent_id')
            ->orderBy('sort_order');
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

    // Scopes
    
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeWithStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    public function scopeFeatured($query)
    {
        return $query->where('featured', true)
            ->orderBy('sort_order');
    }

    // Helper Methods
    
    public function isPublished(): bool
    {
        return $this->published_at !== null && $this->published_at <= now();
    }

    public function publish(): void
    {
        $this->update(['published_at' => now()]);
    }

    public function unpublish(): void
    {
        $this->update(['published_at' => null]);
    }

    public function getRouteKeyName()
    {
        return 'slug';
    }

    /**
     * Scope exhibits visible to a given user.
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
