<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Term extends Model
{
    protected $fillable = [
        'taxonomy_id',
        'name',
        'slug',
        'description',
        'parent_id',
    ];

    protected static function boot()
    {
        parent::boot();
        
        // Auto-generate slug from name if not provided
        static::creating(function ($term) {
            if (empty($term->slug)) {
                $slug = Str::slug($term->name);
                $originalSlug = $slug;
                $counter = 1;
                
                // Ensure slug is unique within this taxonomy
                while (static::where('taxonomy_id', $term->taxonomy_id)
                    ->where('slug', $slug)
                    ->exists()) {
                    $slug = $originalSlug . '-' . $counter;
                    $counter++;
                }
                
                $term->slug = $slug;
            }
        });
    }

    public function taxonomy()
    {
        return $this->belongsTo(Taxonomy::class);
    }

    public function parent()
    {
        return $this->belongsTo(Term::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Term::class, 'parent_id')->orderBy('name');
    }

    // Polymorphic relationships to resources
    
    public function items()
    {
        return $this->morphedByMany(Item::class, 'termable')
            ->withTimestamps();
    }

    public function collections()
    {
        return $this->morphedByMany(Collection::class, 'termable')
            ->withTimestamps();
    }

    public function exhibits()
    {
        return $this->morphedByMany(Exhibit::class, 'termable')
            ->withTimestamps();
    }

    public function exhibitPages()
    {
        return $this->morphedByMany(ExhibitPage::class, 'termable')
            ->withTimestamps();
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName()
    {
        return 'slug';
    }

    /**
     * Check if this taxonomy is hierarchical and can have parent/child terms.
     */
    public function isHierarchical(): bool
    {
        return $this->taxonomy?->hierarchical ?? false;
    }
}
