<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Taxonomy extends Model
{
    protected $fillable = [
        'key',
        'name',
        'description',
        'hierarchical',
    ];

    protected $casts = [
        'hierarchical' => 'boolean',
    ];

    public function terms()
    {
        return $this->hasMany(Term::class);
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName()
    {
        return 'key';
    }
}
