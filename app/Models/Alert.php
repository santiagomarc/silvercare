<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Alert extends Model
{
    use HasFactory;

    protected $fillable = [
        'elderly_id',
        'severity',
        'source_type',
        'source_id',
        'title',
        'message',
        'metadata',
        'state',
        'escalate_at',
        'acknowledged_at',
        'acknowledged_by',
        'resolved_at',
        'resolved_by',
    ];

    protected $casts = [
        'metadata' => 'array',
        'escalate_at' => 'datetime',
        'acknowledged_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function elderly(): BelongsTo
    {
        return $this->belongsTo(UserProfile::class, 'elderly_id');
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(AlertDelivery::class);
    }

    public function acknowledgedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    // Scopes
    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('state', 'open');
    }

    public function scopeUnacknowledged(Builder $query): Builder
    {
        return $query->where('state', 'open');
    }

    public function scopeCritical(Builder $query): Builder
    {
        return $query->whereIn('severity', ['critical', 'emergency']);
    }

    // State Transitions
    public function isOpen(): bool
    {
        return $this->state === 'open';
    }

    public function isAcknowledged(): bool
    {
        return $this->state === 'acknowledged';
    }

    public function isResolved(): bool
    {
        return $this->state === 'resolved';
    }

    public function acknowledge(int $userId): void
    {
        $this->update([
            'state' => 'acknowledged',
            'acknowledged_at' => Carbon::now(),
            'acknowledged_by' => $userId,
        ]);
    }

    public function resolve(int $userId): void
    {
        $this->update([
            'state' => 'resolved',
            'resolved_at' => Carbon::now(),
            'resolved_by' => $userId,
        ]);
    }
}
