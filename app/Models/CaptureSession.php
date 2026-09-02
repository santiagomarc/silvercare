<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CaptureSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'elderly_id',
        'session_type',
        'image_path',
        'raw_transcript',
        'extracted_data',
        'status',
        'error_message',
        'confirmed_at',
        'expires_at',
    ];

    protected $casts = [
        'extracted_data' => 'array',
        'confirmed_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function elderly(): BelongsTo
    {
        return $this->belongsTo(UserProfile::class, 'elderly_id');
    }

    /**
     * M5: the retention deadline for the uploaded image. A capture exists only
     * long enough for the patient to confirm what was read from it.
     */
    public function scopeExpired(Builder $query, ?\Carbon\Carbon $now = null): Builder
    {
        return $query->whereNotNull('expires_at')
            ->where('expires_at', '<=', $now ?? now());
    }

    public function isExpired(?\Carbon\Carbon $now = null): bool
    {
        return $this->expires_at !== null && $this->expires_at->lte($now ?? now());
    }

    // Scopes
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeProcessed(Builder $query): Builder
    {
        return $query->where('status', 'processed');
    }

    public function scopeConfirmed(Builder $query): Builder
    {
        return $query->where('status', 'confirmed');
    }

    // Helpers
    public function isConfirmed(): bool
    {
        return $this->status === 'confirmed';
    }

    public function markConfirmed(): void
    {
        $this->update([
            'status' => 'confirmed',
            'confirmed_at' => Carbon::now(),
        ]);
    }
}
