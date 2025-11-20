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
        'processing_status',
        'processing_error',
        'processed_at',
        'metadata',
        'meta', // Legacy field, keeping for backward compatibility
    ];

    protected $casts = [
        'meta' => 'array',
        'metadata' => 'array',
        'is_transcript' => 'boolean',
        'processed_at' => 'datetime',
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

    // Processing status helpers
    
    /**
     * Check if media is ready for display.
     */
    public function isReady(): bool
    {
        return $this->processing_status === 'ready';
    }

    /**
     * Check if media is currently being processed.
     */
    public function isProcessing(): bool
    {
        return in_array($this->processing_status, ['uploading', 'uploaded', 'processing']);
    }

    /**
     * Check if media processing failed.
     */
    public function hasFailed(): bool
    {
        return $this->processing_status === 'failed';
    }

    /**
     * Mark media as uploaded and ready for processing.
     */
    public function markAsUploaded(): void
    {
        $this->update(['processing_status' => 'uploaded']);
    }

    /**
     * Mark media as currently processing.
     */
    public function markAsProcessing(): void
    {
        $this->update(['processing_status' => 'processing']);
    }

    /**
     * Mark media as ready (processing complete).
     */
    public function markAsReady(): void
    {
        $this->update([
            'processing_status' => 'ready',
            'processed_at' => now(),
            'processing_error' => null,
        ]);
    }

    /**
     * Mark media processing as failed with error message.
     */
    public function markAsFailed(string $error): void
    {
        $this->update([
            'processing_status' => 'failed',
            'processing_error' => $error,
        ]);
    }

    /**
     * Get duration in human-readable format.
     */
    public function getFormattedDurationAttribute(): ?string
    {
        $seconds = $this->metadata['duration'] ?? null;
        
        if (!$seconds) {
            return null;
        }

        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $seconds = $seconds % 60;

        if ($hours > 0) {
            return sprintf('%d:%02d:%02d', $hours, $minutes, $seconds);
        }

        return sprintf('%d:%02d', $minutes, $seconds);
    }
}
