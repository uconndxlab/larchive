<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;

class Collection extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title',
        'slug',
        'visibility',
        'status',
        'description',
        'published_at',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    public function items()
    {
        return $this->hasMany(Item::class);
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

    /**
     * Scope collections visible to a given user.
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
     * Scope collections by status.
     */
    public function scopeWithStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to only published collections.
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }
}
