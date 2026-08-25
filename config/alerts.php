<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Clinical Alert Thresholds
    |--------------------------------------------------------------------------
    |
    | Baseline threshold rules used when a patient does not have custom
    | caregiver-approved overrides configured in patient_alert_thresholds.
    |
    */

    'thresholds' => [
        'blood_pressure' => [
            'critical_systolic_high' => 180,
            'critical_systolic_low' => 85,
            'critical_diastolic_high' => 120,
            'critical_diastolic_low' => 50,
            'warning_systolic_high' => 140,
            'warning_diastolic_high' => 90,
        ],
        'sugar_level' => [
            'critical_high' => 250,
            'critical_low' => 60,
            'warning_high' => 180,
            'warning_low' => 70,
        ],
        'temperature' => [
            'critical_high' => 39.5,
            'critical_low' => 35.0,
            'warning_high' => 38.0,
            'warning_low' => 35.5,
        ],
        'heart_rate' => [
            'critical_high' => 120,
            'critical_low' => 45,
            'warning_high' => 100,
            'warning_low' => 50,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Alert Escalation Timing
    |--------------------------------------------------------------------------
    |
    | Minutes before an unacknowledged alert triggers secondary escalation.
    |
    */
    'escalation_minutes' => [
        'emergency' => 10,
        'critical'  => 15,
        'warning'   => 60,
        'info'      => 1440,
    ],

    /*
    |--------------------------------------------------------------------------
    | Emergency Guidance Notice
    |--------------------------------------------------------------------------
    */
    'emergency_disclaimer' => 'If this is a life-threatening medical emergency, call your local emergency services (e.g. 911) immediately. SilverCare is a care-coordination tool, not an emergency response service.',
];
