<?php

namespace App\Models;

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

    /**
     * Get the status of a specific dose time.
     */
    public function getDoseStatus(string $time, ?MedicationLog $log): array
    {
        $now = now();
        $scheduledTime = \Carbon\Carbon::parse(today()->format('Y-m-d') . ' ' . $time);
        $windowStart = $scheduledTime->copy(); // Window starts AT scheduled time
        $windowEnd = $scheduledTime->copy()->addMinutes(60); // 1 hour grace period after
        
        $isWithinWindow = $now->between($windowStart, $windowEnd);
        $isPastWindow = $now->gt($windowEnd);
        $isBeforeWindow = $now->lt($windowStart);
        $isTaken = $log?->is_taken ?? false;
        $takenAt = $log?->taken_at;
        
        $canTake = $isWithinWindow || $isPastWindow;
        $canUndo = !$isPastWindow; // Can only undo within the grace period
        
        if ($isTaken) {
            $wasLate = $takenAt && $takenAt->gt($windowEnd);
            return [
                'status' => $wasLate ? 'Taken Late' : 'Taken',
                'icon' => '✓',
                'bg' => $wasLate ? 'bg-orange-50 border-orange-300' : 'bg-green-50 border-green-300',
                'iconBg' => $wasLate ? 'bg-orange-200' : 'bg-green-200',
                'canTake' => false,
                'canUndo' => $canUndo,
                'isTaken' => true,
                'isWithinWindow' => false,
            ];
        }
        
        if ($isPastWindow) {
            return [
                'status' => 'Missed', 
                'icon' => '!', 
                'bg' => 'bg-red-50 border-red-300', 
                'iconBg' => 'bg-red-200', 
                'canTake' => true, 
                'canUndo' => false,
                'isTaken' => false,
                'isWithinWindow' => false,
            ];
        }
        
        if ($isWithinWindow) {
            return [
                'status' => 'Take Now', 
                'icon' => '●', 
                'bg' => 'bg-amber-50 border-amber-300', 
                'iconBg' => 'bg-amber-200', 
                'canTake' => true, 
                'canUndo' => true,
                'isTaken' => false,
                'isWithinWindow' => true,
            ];
        }
        
        return [
            'status' => 'Upcoming', 
            'icon' => '○', 
            'bg' => 'bg-white border-gray-200', 
            'iconBg' => 'bg-green-100', 
            'canTake' => false, 
            'canUndo' => false,
            'isTaken' => false,
            'isWithinWindow' => false,
        ];
    }
}
