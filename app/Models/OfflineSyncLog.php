<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OfflineSyncLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'elderly_id',
        'client_mutation_id',
        'action_type',
        'payload',
        'status',
        'error_code',
        'applied_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'applied_at' => 'datetime',
    ];

    public function elderly(): BelongsTo
    {
        return $this->belongsTo(UserProfile::class, 'elderly_id');
    }

    public function scopeApplied(Builder $query): Builder
    {
        return $query->where('status', 'applied');
    }

    public function scopeConflicts(Builder $query): Builder
    {
        return $query->where('status', 'conflict_skipped');
    }

    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('status', 'failed');
    }
}
