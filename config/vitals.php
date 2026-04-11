<?php

return [
    'blood_pressure' => [
        'name' => 'Blood Pressure',
        'unit' => 'mmHg',
        'icon' => '❤️',
        'color' => 'red',
        'has_text_value' => true,
        'score_thresholds' => [
            ['max_systolic' => 119, 'max_diastolic' => 79, 'status' => 'Optimal', 'score' => 100],
            ['max_systolic' => 129, 'max_diastolic' => 84, 'status' => 'Normal', 'score' => 85],
            ['max_systolic' => 139, 'max_diastolic' => 89, 'status' => 'Elevated', 'score' => 70],
            ['status' => 'High', 'score' => 50, 'default' => true],
        ],
        'status_thresholds' => [
            'critical' => ['systolic' => 180, 'diastolic' => 120],
            'high' => ['systolic' => 140, 'diastolic' => 90],
            'elevated' => ['systolic' => 130, 'diastolic' => 80],
            'low' => ['systolic' => 90, 'diastolic' => 60],
        ],
    ],
    'sugar_level' => [
        'name' => 'Sugar Level',
        'unit' => 'mg/dL',
        'icon' => '🩸',
        'color' => 'blue',
        'has_text_value' => false,
        'min' => 50,
        'max' => 500,
        'score_thresholds' => [
            ['min' => 70, 'max' => 100, 'status' => 'Optimal', 'score' => 100],
            ['min' => 60, 'max' => 125, 'status' => 'Normal', 'score' => 80],
            ['status' => 'Attention', 'score' => 60, 'default' => true],
        ],
        'status_thresholds' => [
            ['min' => 250, 'label' => 'Critical', 'tone' => 'red'],
            ['min' => 180, 'label' => 'High', 'tone' => 'orange'],
            ['min' => 126, 'label' => 'Elevated', 'tone' => 'yellow'],
            ['max' => 69, 'label' => 'Low', 'tone' => 'blue'],
            ['label' => 'Normal', 'tone' => 'green', 'default' => true],
        ],
    ],
    'temperature' => [
        'name' => 'Temperature',
        'unit' => '°C',
        'icon' => '🌡️',
        'color' => 'orange',
        'has_text_value' => false,
        'min' => 35,
        'max' => 42,
        'score_thresholds' => [
            ['min' => 36.1, 'max' => 37.2, 'status' => 'Normal', 'score' => 100],
            ['min' => 35.5, 'max' => 37.8, 'status' => 'Mild', 'score' => 75],
            ['status' => 'Attention', 'score' => 50, 'default' => true],
        ],
        'status_thresholds' => [
            ['min' => 39.5, 'label' => 'High Fever', 'tone' => 'red'],
            ['min' => 38.0, 'label' => 'Fever', 'tone' => 'orange'],
            ['min' => 37.3, 'label' => 'Elevated', 'tone' => 'yellow'],
            ['max' => 35.9, 'label' => 'Low', 'tone' => 'blue'],
            ['label' => 'Normal', 'tone' => 'green', 'default' => true],
        ],
    ],
    'heart_rate' => [
        'name' => 'Heart Rate',
        'unit' => 'bpm',
        'icon' => '💓',
        'color' => 'rose',
        'has_text_value' => false,
        'min' => 40,
        'max' => 200,
        'score_thresholds' => [
            ['min' => 60, 'max' => 100, 'status' => 'Optimal', 'score' => 100],
            ['min' => 50, 'max' => 110, 'status' => 'Normal', 'score' => 80],
            ['status' => 'Attention', 'score' => 60, 'default' => true],
        ],
        'status_thresholds' => [
            ['min' => 150, 'label' => 'Critical', 'tone' => 'red'],
            ['min' => 100, 'label' => 'High', 'tone' => 'orange'],
            ['max' => 49, 'label' => 'Very Low', 'tone' => 'red'],
            ['max' => 59, 'label' => 'Low', 'tone' => 'blue'],
            ['label' => 'Normal', 'tone' => 'green', 'default' => true],
        ],
    ],
    'mood' => [
        'name' => 'Mood',
        'unit' => '',
        'icon' => '😊',
        'color' => 'purple',
        'has_text_value' => false,
        'min' => 1,
        'max' => 5,
    ],
    'steps' => [
        'name' => 'Steps',
        'unit' => 'steps',
        'icon' => '👟',
        'color' => 'green',
        'has_text_value' => false,
        'min' => 0,
        'max' => 100000,
    ],

    // Subset used for analytics scoring (excludes mood, steps)
    'scorable_types' => ['blood_pressure', 'sugar_level', 'temperature', 'heart_rate'],

    // Required daily vitals for elderly dashboard progress
    'required_daily' => ['heart_rate', 'blood_pressure', 'sugar_level', 'temperature'],
];
