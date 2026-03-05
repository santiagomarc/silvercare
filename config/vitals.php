<?php

return [
    'blood_pressure' => [
        'name' => 'Blood Pressure',
        'unit' => 'mmHg',
        'icon' => '❤️',
        'color' => 'red',
        'has_text_value' => true,
    ],
    'sugar_level' => [
        'name' => 'Sugar Level',
        'unit' => 'mg/dL',
        'icon' => '🩸',
        'color' => 'blue',
        'has_text_value' => false,
        'min' => 50,
        'max' => 500,
    ],
    'temperature' => [
        'name' => 'Temperature',
        'unit' => '°C',
        'icon' => '🌡️',
        'color' => 'orange',
        'has_text_value' => false,
        'min' => 35,
        'max' => 42,
    ],
    'heart_rate' => [
        'name' => 'Heart Rate',
        'unit' => 'bpm',
        'icon' => '💓',
        'color' => 'rose',
        'has_text_value' => false,
        'min' => 40,
        'max' => 200,
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
