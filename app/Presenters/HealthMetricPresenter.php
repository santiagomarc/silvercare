<?php

namespace App\Presenters;

class HealthMetricPresenter
{
    public static function getHeartRateStatus($value)
    {
        return self::resolveScalarStatus($value, config('vitals.heart_rate.status_thresholds', []));
    }

    public static function getBloodPressureStatus($value)
    {
        if (!$value || !str_contains($value, '/')) {
            return self::statusPayload('Unknown', 'gray');
        }
        
        $parts = explode('/', $value);
        $systolic = (int)$parts[0];
        $diastolic = (int)($parts[1] ?? 0);
        $thresholds = config('vitals.blood_pressure.status_thresholds', []);

        if ($systolic >= intval($thresholds['critical']['systolic'] ?? 180) || $diastolic >= intval($thresholds['critical']['diastolic'] ?? 120)) {
            return self::statusPayload('Critical', 'red');
        }

        if ($systolic >= intval($thresholds['high']['systolic'] ?? 140) || $diastolic >= intval($thresholds['high']['diastolic'] ?? 90)) {
            return self::statusPayload('High', 'orange');
        }

        if ($systolic >= intval($thresholds['elevated']['systolic'] ?? 130) || $diastolic >= intval($thresholds['elevated']['diastolic'] ?? 80)) {
            return self::statusPayload('Elevated', 'yellow');
        }

        if ($systolic < intval($thresholds['low']['systolic'] ?? 90) || $diastolic < intval($thresholds['low']['diastolic'] ?? 60)) {
            return self::statusPayload('Low', 'blue');
        }

        return self::statusPayload('Normal', 'green');
    }

    public static function getSugarLevelStatus($value)
    {
        return self::resolveScalarStatus($value, config('vitals.sugar_level.status_thresholds', []));
    }

    public static function getTemperatureStatus($value)
    {
        return self::resolveScalarStatus($value, config('vitals.temperature.status_thresholds', []));
    }

    private static function resolveScalarStatus($value, array $rules): array
    {
        if (!is_numeric($value)) {
            return self::statusPayload('Unknown', 'gray');
        }

        $numeric = floatval($value);
        $default = self::statusPayload('Unknown', 'gray');

        foreach ($rules as $rule) {
            if (($rule['default'] ?? false) === true) {
                $default = self::statusPayload(
                    strval($rule['label'] ?? 'Unknown'),
                    strval($rule['tone'] ?? 'gray')
                );
                continue;
            }

            if (array_key_exists('min', $rule) && $numeric < floatval($rule['min'])) {
                continue;
            }

            if (array_key_exists('max', $rule) && $numeric > floatval($rule['max'])) {
                continue;
            }

            if (array_key_exists('min', $rule) || array_key_exists('max', $rule)) {
                return self::statusPayload(
                    strval($rule['label'] ?? 'Unknown'),
                    strval($rule['tone'] ?? 'gray')
                );
            }
        }

        return $default;
    }

    private static function statusPayload(string $label, string $tone): array
    {
        $palette = [
            'red' => ['color' => 'red', 'bg' => 'bg-red-100', 'text' => 'text-red-700'],
            'orange' => ['color' => 'orange', 'bg' => 'bg-orange-100', 'text' => 'text-orange-700'],
            'yellow' => ['color' => 'yellow', 'bg' => 'bg-yellow-100', 'text' => 'text-yellow-700'],
            'blue' => ['color' => 'blue', 'bg' => 'bg-blue-100', 'text' => 'text-blue-700'],
            'green' => ['color' => 'green', 'bg' => 'bg-green-100', 'text' => 'text-green-700'],
            'gray' => ['color' => 'gray', 'bg' => 'bg-gray-100', 'text' => 'text-gray-700'],
        ];

        $styles = $palette[$tone] ?? $palette['gray'];

        return [
            'label' => $label,
            'color' => $styles['color'],
            'bg' => $styles['bg'],
            'text' => $styles['text'],
        ];
    }

    /**
     * Return trend icon and CSS color class for a trend string.
     */
    public static function trendDisplay(string $trend): array
    {
        return match ($trend) {
            'increasing' => ['icon' => '↗', 'color' => 'text-red-600'],
            'decreasing' => ['icon' => '↘', 'color' => 'text-green-600'],
            default       => ['icon' => '→', 'color' => 'text-gray-600'],
        };
    }
}
