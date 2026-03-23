<?php

namespace App\Services;

use Carbon\Carbon;

class MedicationWindowService
{
    public const DEFAULT_GRACE_MINUTES = 60;

    /**
     * @return array{scheduled_time: Carbon, window_start: Carbon, window_end: Carbon, is_within_window: bool, is_past_window: bool, is_before_window: bool, can_take: bool, can_undo: bool}
     */
    public function forToday(string $time, ?Carbon $now = null, int $graceMinutes = self::DEFAULT_GRACE_MINUTES): array
    {
        $current = $now?->copy() ?? Carbon::now();
        $scheduledDateTime = Carbon::parse(Carbon::today()->format('Y-m-d') . ' ' . $time);

        return $this->build($scheduledDateTime, $current, $graceMinutes);
    }

    /**
     * @return array{scheduled_time: Carbon, window_start: Carbon, window_end: Carbon, is_within_window: bool, is_past_window: bool, is_before_window: bool, can_take: bool, can_undo: bool}
     */
    public function build(Carbon $scheduledDateTime, ?Carbon $now = null, int $graceMinutes = self::DEFAULT_GRACE_MINUTES): array
    {
        $current = $now?->copy() ?? Carbon::now();
        $windowStart = $scheduledDateTime->copy()->subMinutes($graceMinutes);
        $windowEnd = $scheduledDateTime->copy()->addMinutes($graceMinutes);

        $isWithinWindow = $current->between($windowStart, $windowEnd);
        $isPastWindow = $current->gt($windowEnd);
        $isBeforeWindow = $current->lt($windowStart);

        return [
            'scheduled_time' => $scheduledDateTime,
            'window_start' => $windowStart,
            'window_end' => $windowEnd,
            'is_within_window' => $isWithinWindow,
            'is_past_window' => $isPastWindow,
            'is_before_window' => $isBeforeWindow,
            'can_take' => $isWithinWindow || $isPastWindow,
            'can_undo' => !$isPastWindow,
        ];
    }
}
