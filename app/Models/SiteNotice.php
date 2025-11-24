<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteNotice extends Model
{
    protected $fillable = [
        'enabled',
        'title',
        'body',
    ];

    protected $casts = [
        'enabled' => 'boolean',
    ];

    /**
     * Get the singleton instance of the site notice.
     * Creates one if it doesn't exist.
     */
    public static function instance()
    {
        $notice = static::first();

        if (!$notice) {
            $notice = static::create([
                'enabled' => false,
                'title' => 'Welcome to Larchive',
                'body' => 'Please read and accept our terms to continue.',
            ]);
        }

        return $notice;
    }

    /**
     * Check if the notice is currently enabled and should be shown.
     */
    public function shouldShow(): bool
    {
        return $this->enabled && !empty($this->title) && !empty($this->body);
    }
}
