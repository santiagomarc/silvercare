<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DoseInstance extends Model
{
    use HasFactory;

    protected $table = 'medication_dose_instances';

    protected $fillable = [
        'elderly_id',
        'medication_id',
        'scheduled_at_utc',
        'local_date',
        'timezone',
        'state',
        'taken_at',
        'source',
        'idempotency_key',
        'actor_id',
        'version',
        'notes',
    ];

    protected $casts = [
        'scheduled_at_utc' => 'datetime',
        'local_date' => 'date:Y-m-d',
        'taken_at' => 'datetime',
        'version' => 'integer',
    ];

    public function getScheduledAtUtcAttribute($value): ?Carbon
    {
        return $value ? Carbon::parse($value, 'UTC') : null;
    }

    public function getLocalDateAttribute($value): ?string
    {
        if (!$value) return null;
        return substr((string) $value, 0, 10);
    }

    public function elderly(): BelongsTo
    {
        return $this->belongsTo(UserProfile::class, 'elderly_id');
    }

    public function medication(): BelongsTo
    {
        return $this->belongsTo(Medication::class, 'medication_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    // Scopes
    public function scopePending(Builder $query): Builder
    {
        return $query->where('state', 'pending');
    }

    public function scopeTaken(Builder $query): Builder
    {
        return $query->whereIn('state', ['taken', 'taken_late']);
    }

    public function scopeForDate(Builder $query, Carbon $date): Builder
    {
        return $query->whereDate('local_date', $date->format('Y-m-d'));
    }

    public function scopeForElderly(Builder $query, int $elderlyId): Builder
    {
        return $query->where('elderly_id', $elderlyId);
    }

    // State helpers
    public function isPending(): bool
    {
        return $this->state === 'pending';
    }

    public function isTaken(): bool
    {
        return in_array($this->state, ['taken', 'taken_late'], true);
    }

    public function isMissed(): bool
    {
        return $this->state === 'missed';
    }
}
