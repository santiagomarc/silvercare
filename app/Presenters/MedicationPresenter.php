<?php

namespace App\Presenters;

use App\Models\MedicationLog;
use App\Services\DoseAdministrationService;

/**
 * Turns a dose and its log into the words, the icon name and the tone a view
 * needs. It hands back `icon` (a sprite id), `doseClass` (the same
 * `dose-*` class medication-tracker.js swaps at runtime, so the server and
 * the browser agree) and `tone` — never a colour, and never an emoji.
 */
class MedicationPresenter
{
    /**
     * Get the status of a specific dose time.
     */
    public static function getDoseStatus(string $time, ?MedicationLog $log): array
    {
        $window = app(DoseAdministrationService::class)->evaluateWindowForTime($time);
        $windowEnd = $window['window_end'];
        $isWithinWindow = $window['is_within_window'];
        $isPastWindow = $window['is_past_window'];
        $isTaken = $log?->is_taken ?? false;
        $takenAt = $log?->taken_at;

        $canTake = $window['can_take'];
        $canUndo = $window['can_undo'];
        
        if ($isTaken) {
            $wasLate = $takenAt && $takenAt->gt($windowEnd);
            return [
                'status' => $wasLate ? 'Taken Late' : 'Taken',
                'icon' => 'check',
                'doseClass' => $wasLate ? 'dose-taken-late' : 'dose-taken',
                'tone' => $wasLate ? 'warn' : 'ok',
                'canTake' => false,
                'canUndo' => $canUndo,
                'isTaken' => true,
                'isWithinWindow' => false,
            ];
        }
        
        if ($isPastWindow) {
            // C5: past the outer bound the dose can no longer be confirmed —
            // marking it taken hours later would misreport it to the caregiver.
            // The honest action then is to skip it with a reason.
            $isExpired = $window['is_expired'];

            return [
                'status' => $isExpired ? 'Missed' : 'Late — take now',
                'icon' => 'alert',
                'doseClass' => 'dose-missed',
                'tone' => 'alert',
                'canTake' => $canTake,
                'canUndo' => false,
                'canSkip' => $isExpired,
                'isTaken' => false,
                'isExpired' => $isExpired,
                'isWithinWindow' => false,
            ];
        }
        
        if ($isWithinWindow) {
            return [
                'status' => 'Take Now',
                'icon' => 'pill',
                'doseClass' => 'dose-active',
                'tone' => 'brand',
                'canTake' => true, 
                'canUndo' => false,
                'isTaken' => false,
                'isWithinWindow' => true,
            ];
        }
        
        return [
            'status' => 'Upcoming',
            'icon' => 'clock',
            'doseClass' => 'dose-upcoming',
            'tone' => '',
            'canTake' => false, 
            'canUndo' => false,
            'isTaken' => false,
            'isWithinWindow' => false,
        ];
    }

    /**
     * Parses the instructions text for common, crucial tags.
     */
    public static function parseInstructionTags(?string $instructions): array
    {
        if (!$instructions) {
            return [];
        }

        $tags = [];
        $lowerSrc = strtolower($instructions);

        if (str_contains($lowerSrc, 'food') || str_contains($lowerSrc, 'eat') || str_contains($lowerSrc, 'meal')) {
            $tags[] = ['text' => 'Take with food', 'tone' => 'warn'];
        } elseif (str_contains($lowerSrc, 'empty stomach') || str_contains($lowerSrc, 'fasting') || str_contains($lowerSrc, 'before meal')) {
            $tags[] = ['text' => 'Empty stomach', 'tone' => 'brand'];
        }
        
        if (str_contains($lowerSrc, 'water') || str_contains($lowerSrc, 'drink') || str_contains($lowerSrc, 'fluid')) {
            $tags[] = ['text' => 'Drink water', 'tone' => 'brand'];
        }

        return collect($tags)->unique('text')->values()->all();
    }
}
