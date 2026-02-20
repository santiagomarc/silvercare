<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class HealthMetric extends Model
{
    protected $fillable = [
        'elderly_id',
        'type',
        'value',
        'value_text',
        'unit',
        'measured_at',
        'source',
        'notes',
    ];

    protected $casts = [
        'measured_at' => 'datetime',
        'value' => 'decimal:2',
    ];

    public function elderly(): BelongsTo
    {
        return $this->belongsTo(UserProfile::class, 'elderly_id');
    }

    // Query scopes
    public function scopeHeartRate(Builder $query): Builder
    {
        return $query->where('type', 'heart_rate');
    }

    public function scopeBloodPressure(Builder $query): Builder
    {
        return $query->where('type', 'blood_pressure');
    }

    public function scopeSugarLevel(Builder $query): Builder
    {
        return $query->where('type', 'sugar_level');
    }

    public function scopeTemperature(Builder $query): Builder
    {
        return $query->where('type', 'temperature');
    }

    public function scopeManual(Builder $query): Builder
    {
        return $query->where('source', 'manual');
    }

    public function scopeGoogleFit(Builder $query): Builder
    {
        return $query->where('source', 'google_fit');
    }

    public function scopeMood(Builder $query): Builder
    {
        return $query->where('type', 'mood');
    }

    public function scopeSteps(Builder $query): Builder
    {
        return $query->where('type', 'steps');
    }
}
