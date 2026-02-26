<?php

namespace App\Presenters;

class HealthMetricPresenter
{
    public static function getHeartRateStatus($value)
    {
        if ($value >= 150) return ['label' => 'Critical', 'color' => 'red', 'bg' => 'bg-red-100', 'text' => 'text-red-700'];
        if ($value >= 100) return ['label' => 'High', 'color' => 'orange', 'bg' => 'bg-orange-100', 'text' => 'text-orange-700'];
        if ($value < 50) return ['label' => 'Very Low', 'color' => 'red', 'bg' => 'bg-red-100', 'text' => 'text-red-700'];
        if ($value < 60) return ['label' => 'Low', 'color' => 'blue', 'bg' => 'bg-blue-100', 'text' => 'text-blue-700'];
        return ['label' => 'Normal', 'color' => 'green', 'bg' => 'bg-green-100', 'text' => 'text-green-700'];
    }

    public static function getBloodPressureStatus($value)
    {
        if (!$value || !str_contains($value, '/')) {
            return ['label' => 'Unknown', 'color' => 'gray', 'bg' => 'bg-gray-100', 'text' => 'text-gray-700'];
        }
        
        $parts = explode('/', $value);
        $systolic = (int)$parts[0];
        $diastolic = (int)($parts[1] ?? 0);

        if ($systolic >= 180 || $diastolic >= 120) return ['label' => 'Critical', 'color' => 'red', 'bg' => 'bg-red-100', 'text' => 'text-red-700'];
        if ($systolic >= 140 || $diastolic >= 90) return ['label' => 'High', 'color' => 'orange', 'bg' => 'bg-orange-100', 'text' => 'text-orange-700'];
        if ($systolic >= 130 || $diastolic >= 80) return ['label' => 'Elevated', 'color' => 'yellow', 'bg' => 'bg-yellow-100', 'text' => 'text-yellow-700'];
        if ($systolic < 90 || $diastolic < 60) return ['label' => 'Low', 'color' => 'blue', 'bg' => 'bg-blue-100', 'text' => 'text-blue-700'];
        return ['label' => 'Normal', 'color' => 'green', 'bg' => 'bg-green-100', 'text' => 'text-green-700'];
    }

    public static function getSugarLevelStatus($value)
    {
        if ($value >= 250) return ['label' => 'Critical', 'color' => 'red', 'bg' => 'bg-red-100', 'text' => 'text-red-700'];
        if ($value >= 180) return ['label' => 'High', 'color' => 'orange', 'bg' => 'bg-orange-100', 'text' => 'text-orange-700'];
        if ($value >= 126) return ['label' => 'Elevated', 'color' => 'yellow', 'bg' => 'bg-yellow-100', 'text' => 'text-yellow-700'];
        if ($value < 70) return ['label' => 'Low', 'color' => 'blue', 'bg' => 'bg-blue-100', 'text' => 'text-blue-700'];
        return ['label' => 'Normal', 'color' => 'green', 'bg' => 'bg-green-100', 'text' => 'text-green-700'];
    }

    public static function getTemperatureStatus($value)
    {
        if ($value >= 39.5) return ['label' => 'High Fever', 'color' => 'red', 'bg' => 'bg-red-100', 'text' => 'text-red-700'];
        if ($value >= 38.0) return ['label' => 'Fever', 'color' => 'orange', 'bg' => 'bg-orange-100', 'text' => 'text-orange-700'];
        if ($value >= 37.3) return ['label' => 'Elevated', 'color' => 'yellow', 'bg' => 'bg-yellow-100', 'text' => 'text-yellow-700'];
        if ($value < 36.0) return ['label' => 'Low', 'color' => 'blue', 'bg' => 'bg-blue-100', 'text' => 'text-blue-700'];
        return ['label' => 'Normal', 'color' => 'green', 'bg' => 'bg-green-100', 'text' => 'text-green-700'];
    }
}
