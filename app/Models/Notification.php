<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    public const TYPE_MEDICATION_REFILL_CAREGIVER = 'medication_refill_caregiver';

    protected $fillable = [
        'elderly_id',
        'type',
        'title',
        'message',
        'severity',
        'metadata',
        'is_read',
        'custom_id',
    ];

    protected $casts = [
        'metadata' => 'array',
        'is_read' => 'boolean',
    ];

    public function elderly(): BelongsTo
    {
        return $this->belongsTo(UserProfile::class, 'elderly_id');
    }

    /**
     * Scope notifications that are safe for elderly-facing UIs/APIs.
     */
    public function scopeForElderly(Builder $query): Builder
    {
        return $query->where('type', '!=', self::TYPE_MEDICATION_REFILL_CAREGIVER);
    }
}
