<?php

namespace App\Models;

use App\Services\DoseAdministrationService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MedicationLog extends Model
{
    protected $fillable = [
        'elderly_id',
        'medication_id',
        'scheduled_time',
        'is_taken',
        'taken_at',
    ];

    protected $casts = [
        'scheduled_time' => 'datetime',
        'is_taken' => 'boolean',
        'taken_at' => 'datetime',
    ];

    public function elderly(): BelongsTo
    {
        return $this->belongsTo(UserProfile::class, 'elderly_id');
    }

    public function medication(): BelongsTo
    {
        return $this->belongsTo(Medication::class);
    }

    // Helper to check if dose was taken late.
    // Defaults to DoseAdministrationService::DEFAULT_GRACE_MINUTES (60 min), the
    // single owner of dose-window rules.
    public function wasTakenLate(int $graceMinutes = DoseAdministrationService::DEFAULT_GRACE_MINUTES): bool
    {
        if (!$this->is_taken || !$this->taken_at) {
            return false;
        }

        $graceDeadline = $this->scheduled_time->copy()->addMinutes($graceMinutes);
        return $this->taken_at->isAfter($graceDeadline);
    }

    // Helper to check if dose is currently missed.
    // Defaults to DoseAdministrationService::DEFAULT_GRACE_MINUTES (60 min), the
    // single owner of dose-window rules.
    public function isMissed(int $graceMinutes = DoseAdministrationService::DEFAULT_GRACE_MINUTES): bool
    {
        if ($this->is_taken) {
            return false;
        }

        $graceDeadline = $this->scheduled_time->copy()->addMinutes($graceMinutes);
        return now()->isAfter($graceDeadline);
    }
}