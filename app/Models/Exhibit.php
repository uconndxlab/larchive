<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Exhibit extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title',
        'slug',
        'description',
        'credits',
        'theme',
        'cover_image',
        'featured',
        'sort_order',
        'published_at',
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
    }

    // Relationships
    
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

    // Scopes
    
    public function scopePublished($query)
    {
        return $query->whereNotNull('published_at')
            ->where('published_at', '<=', now());
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
}
