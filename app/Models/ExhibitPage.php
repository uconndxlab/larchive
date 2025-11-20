<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use App\Models\User;

class ExhibitPage extends Model
{
    protected $fillable = [
        'exhibit_id',
        'parent_id',
        'title',
        'slug',
        'visibility',
        'content',
        'layout_blocks',
        'sort_order',
    ];

    protected $casts = [
        'layout_blocks' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();
        
        // Auto-generate slug from title if not provided
        static::creating(function ($page) {
            if (empty($page->slug)) {
                $slug = Str::slug($page->title);
                $originalSlug = $slug;
                $counter = 1;
                
                // Ensure slug is unique within this exhibit
                while (static::where('exhibit_id', $page->exhibit_id)
                    ->where('slug', $slug)
                    ->exists()) {
                    $slug = $originalSlug . '-' . $counter;
                    $counter++;
                }
                
                $page->slug = $slug;
            }
        });
    }

    // Relationships
    
    public function exhibit()
    {
        return $this->belongsTo(Exhibit::class);
    }

    public function parent()
    {
        return $this->belongsTo(ExhibitPage::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(ExhibitPage::class, 'parent_id')->orderBy('sort_order');
    }

    public function items()
    {
        return $this->belongsToMany(Item::class, 'exhibit_page_item')
            ->withPivot('sort_order', 'caption', 'layout_position')
            ->withTimestamps()
            ->orderByPivot('sort_order');
    }

    // Helper Methods
    
    public function isTopLevel(): bool
    {
        return $this->parent_id === null;
    }

    public function hasChildren(): bool
    {
        return $this->children()->count() > 0;
    }

    public function getFullSlug(): string
    {
        $slugs = [$this->slug];
        $parent = $this->parent;
        
        while ($parent) {
            array_unshift($slugs, $parent->slug);
            $parent = $parent->parent;
        }
        
        return implode('/', $slugs);
    }

    public function getBreadcrumb(): array
    {
        $breadcrumb = [$this];
        $parent = $this->parent;
        
        while ($parent) {
            array_unshift($breadcrumb, $parent);
            $parent = $parent->parent;
        }
        
        return $breadcrumb;
    }

    /**
     * Scope exhibit pages visible to a given user.
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
