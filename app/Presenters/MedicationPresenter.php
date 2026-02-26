<?php

namespace App\Presenters;

use App\Models\MedicationLog;
use Carbon\Carbon;

class MedicationPresenter
{
    /**
     * Get the status of a specific dose time.
     */
    public static function getDoseStatus(string $time, ?MedicationLog $log): array
    {
        $now = now();
        $scheduledTime = Carbon::parse(today()->format('Y-m-d') . ' ' . $time);
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
