<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Media extends Model
{
    protected $fillable = [
        'item_id',
        'filename',
        'path',
        'mime_type',
        'size',
        'alt_text',
        'is_transcript',
        'sort_order',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
        'is_transcript' => 'boolean',
    ];

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    // Scopes
    public function scopeTranscripts($query)
    {
        return $query->where('is_transcript', true);
    }

    public function scopeRegularMedia($query)
    {
        return $query->where('is_transcript', false);
    }
}
