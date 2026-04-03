<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Medication extends Model
{
    protected $fillable = [
        'elderly_id',
        'caregiver_id',
        'name',
        'dosage',
        'dosage_unit',
        'frequency',
        'instructions',
        'days_of_week',
        'specific_dates',
        'times_of_day',
        'start_date',
        'end_date',
        'is_active',
        'track_inventory',
        'current_stock',
        'low_stock_threshold',
    ];

    protected $casts = [
        'days_of_week' => 'array',
        'specific_dates' => 'array',
        'times_of_day' => 'array',
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
        'track_inventory' => 'boolean',
    ];

    public function elderly(): BelongsTo
    {
        return $this->belongsTo(UserProfile::class, 'elderly_id');
    }

    public function caregiver(): BelongsTo
    {
        return $this->belongsTo(UserProfile::class, 'caregiver_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(MedicationLog::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(MedicationSchedule::class);
    }

    /**
     * @return array<int, string>
     */
    public function scheduleTimesForDate(Carbon $date): array
    {
        $schedules = $this->relationLoaded('schedules') ? $this->schedules : $this->schedules()->get();

        if ($schedules->isNotEmpty()) {
            return $schedules
                ->filter(fn (MedicationSchedule $schedule) => $schedule->appliesToDate($date))
                ->pluck('time_of_day')
                ->filter(fn ($time) => is_string($time) && $time !== '')
                ->map(fn ($time) => Carbon::parse($time)->format('H:i'))
                ->unique()
                ->sort()
                ->values()
                ->all();
        }

        if (!$this->isScheduledForDateLegacy($date)) {
            return [];
        }

        return collect($this->times_of_day ?? [])
            ->filter(fn ($time) => is_string($time) && $time !== '')
            ->map(fn ($time) => Carbon::parse($time)->format('H:i'))
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    public function isScheduledForDate(Carbon $date): bool
    {
        $schedules = $this->relationLoaded('schedules') ? $this->schedules : $this->schedules()->get();

        if ($schedules->isNotEmpty()) {
            return $schedules->contains(fn (MedicationSchedule $schedule) => $schedule->appliesToDate($date));
        }

        return $this->isScheduledForDateLegacy($date);
    }

    /**
     * @return array<int, string>
     */
    public function weeklyDays(): array
    {
        $schedules = $this->relationLoaded('schedules') ? $this->schedules : $this->schedules()->get();
        $weekly = $schedules->where('schedule_type', 'weekly')->first();

        if ($weekly) {
            return $weekly->days_of_week ?? [];
        }

        return $this->days_of_week ?? [];
    }

    /**
     * @return array<int, string>
     */
    public function specificScheduleDates(): array
    {
        $schedules = $this->relationLoaded('schedules') ? $this->schedules : $this->schedules()->get();
        $specificDates = $schedules
            ->where('schedule_type', 'specific_date')
            ->pluck('specific_date')
            ->map(fn ($date) => $date instanceof Carbon ? $date->format('Y-m-d') : (string) $date)
            ->filter(fn ($date) => $date !== '')
            ->unique()
            ->values()
            ->all();

        if (!empty($specificDates)) {
            return $specificDates;
        }

        return $this->specific_dates ?? [];
    }

    public function primaryScheduleType(): string
    {
        $schedules = $this->relationLoaded('schedules') ? $this->schedules : $this->schedules()->get();
        $primaryType = $schedules->first()?->schedule_type;

        if (is_string($primaryType) && $primaryType !== '') {
            return $primaryType;
        }

        if (!empty($this->specific_dates)) {
            return 'specific_date';
        }

        if (!empty($this->days_of_week)) {
            return 'weekly';
        }

        return 'daily';
    }

    private function isScheduledForDateLegacy(Carbon $date): bool
    {
        $specificDates = $this->specific_dates ?? [];
        if (!empty($specificDates)) {
            return in_array($date->format('Y-m-d'), $specificDates, true);
        }

        $days = $this->days_of_week ?? [];

        return empty($days) || in_array($date->format('l'), $days, true);
    }
}
