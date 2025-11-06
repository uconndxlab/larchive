<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Exhibit extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title',
        'slug',
        'description',
        'published_at',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    public function items()
    {
        return $this->belongsToMany(Item::class)
            ->withPivot('sort_order', 'caption')
            ->withTimestamps()
            ->orderByPivot('sort_order');
    }
}
