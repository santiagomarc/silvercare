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
        $windowStart = $scheduledTime->copy();
        $windowEnd = $scheduledTime->copy()->addMinutes(60);
        
        $isWithinWindow = $now->between($windowStart, $windowEnd);
        $isPastWindow = $now->gt($windowEnd);
        $isTaken = $log?->is_taken ?? false;
        $takenAt = $log?->taken_at;
        
        $canTake = $isWithinWindow || $isPastWindow;
        $canUndo = !$isPastWindow;
        
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
                'icon' => '💊', 
                'bg' => 'bg-blue-50 border-blue-300 ring-2 ring-blue-400 ring-offset-2', 
                'iconBg' => 'bg-blue-200 animate-pulse', 
                'canTake' => true, 
                'canUndo' => false,
                'isTaken' => false,
                'isWithinWindow' => true,
            ];
        }
        
        return [
            'status' => 'Upcoming', 
            'icon' => '⏳', 
            'bg' => 'bg-gray-50 border-gray-200 opacity-75', 
            'iconBg' => 'bg-gray-200', 
            'canTake' => false, 
            'canUndo' => false,
            'isTaken' => false,
            'isWithinWindow' => false,
        ];
    }
}
