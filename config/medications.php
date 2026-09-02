<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Dose Window
    |--------------------------------------------------------------------------
    |
    | grace_minutes  — how long after the scheduled time a dose still counts as
    |                  taken on time. Inside this window the senior sees
    |                  "Take now"; past it the dose records as taken_late.
    |
    | max_late_minutes — the outer bound. Past this, confirming the dose is no
    |                  longer a truthful record of taking it near its scheduled
    |                  time, so it is refused and the dose must be skipped with
    |                  a reason instead.
    |
    |                  Without this bound an 08:00 dose could be confirmed at
    |                  23:00 — recorded as merely "late", decrementing stock,
    |                  and telling a caregiver the medication was taken when
    |                  what actually happened was a missed dose plus a tap.
    |                  Six hours is long enough for a genuinely delayed dose and
    |                  short enough that it cannot overlap the next one.
    |
    */
    'grace_minutes' => 60,
    'max_late_minutes' => 360,

    /*
    |--------------------------------------------------------------------------
    | Undo
    |--------------------------------------------------------------------------
    |
    | Undo stays available for as long as the dose is still within its grace
    | window. Past that the record has already been reported to the caregiver
    | as late, and silently reversing it would rewrite history they have seen.
    |
    */
    'allow_undo_after_grace' => false,

    /*
    |--------------------------------------------------------------------------
    | Dose Instance Generation
    |--------------------------------------------------------------------------
    |
    | How many days of pending dose instances to keep materialised ahead of now.
    | Seven days so the caregiver's 7-day adherence view has rows to read.
    |
    */
    'generation_horizon_days' => 7,
];
