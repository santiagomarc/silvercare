<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MedicationSchedule extends Model
{
    protected $fillable = [
        'medication_id',
        'schedule_type',
        'days_of_week',
        'specific_date',
        'time_of_day',
    ];

    protected $casts = [
        'days_of_week' => 'array',
        'specific_date' => 'date',
    ];

    public function medication(): BelongsTo
    {
        return $this->belongsTo(Medication::class);
    }

    public function appliesToDate(Carbon $date): bool
    {
        if ($this->schedule_type === 'daily') {
            return true;
        }

        if ($this->schedule_type === 'weekly') {
            return in_array($date->format('l'), $this->days_of_week ?? [], true);
        }

        if ($this->schedule_type === 'specific_date') {
            return $this->specific_date?->isSameDay($date) === true;
        }

        return false;
    }
}
