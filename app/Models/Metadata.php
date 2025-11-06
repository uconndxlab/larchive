<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Metadata extends Model
{
    protected $fillable = [
        'item_id',
        'key',
        'value',
    ];

    public function item()
    {
        return $this->belongsTo(Item::class);
    }
}
