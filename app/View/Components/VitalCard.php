<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class VitalCard extends Component
{
    public string $type;
    public ?array $metricData;
    public string $title;
    public string $unit;
    public string $route;

    /** Lucide icon name; the view renders it through <x-dynamic-component>. */
    public string $icon;

    /**
     * ['label' => string, 'tone' => 'ok'|'warn'|'alert'].
     *
     * A tone, not a colour: the view turns it into `sc-badge-*` so the card
     * follows light, dark and high contrast. The label is what carries the
     * meaning — colour is never the only signal.
     */
    public ?array $status = null;

    /**
     * Create a new component instance.
     */
    public function __construct(string $type, ?array $metricData = null)
    {
        $this->type = $type;
        $this->metricData = $metricData ?? ['recorded' => false];
        
        $this->setupVitalConfig();
        $this->calculateStatus();
    }

    private function setupVitalConfig(): void
    {
        switch ($this->type) {
            case 'blood_pressure':
                $this->title = 'Blood Pressure';
                $this->unit = 'mmHg';
                $this->route = route('elderly.vitals.blood_pressure');
                $this->icon = 'heart-pulse';
                break;
            case 'sugar_level':
                $this->title = 'Sugar Level';
                $this->unit = 'mg/dL';
                $this->route = route('elderly.vitals.sugar_level');
                $this->icon = 'droplet';
                break;
            case 'temperature':
                $this->title = 'Temperature';
                $this->unit = '°C';
                $this->route = route('elderly.vitals.temperature');
                $this->icon = 'thermometer';
                break;
            case 'heart_rate':
                $this->title = 'Heart Rate';
                $this->unit = 'bpm';
                $this->route = route('elderly.vitals.heart_rate');
                $this->icon = 'activity';
                break;
        }
    }

    private function calculateStatus(): void
    {
        if (!($this->metricData['recorded'] ?? false)) {
            return;
        }

        $val = $this->metricData['value'] ?? null;
        $valText = $this->metricData['value_text'] ?? null;

        switch ($this->type) {
            case 'blood_pressure':
                if ($valText) {
                    $parts = explode('/', $valText);
                    if (count($parts) === 2) {
                        $sys = intval($parts[0]);
                        $dia = intval($parts[1]);
                        if ($sys >= 180 || $dia >= 120) {
                            $this->status = ['label' => 'Critical', 'tone' => 'alert'];
                        } elseif ($sys >= 140 || $dia >= 90) {
                            $this->status = ['label' => 'High', 'tone' => 'warn'];
                        } elseif ($sys >= 130 || $dia >= 80) {
                            $this->status = ['label' => 'Elevated', 'tone' => 'warn'];
                        } elseif ($sys < 90 || $dia < 60) {
                            $this->status = ['label' => 'Low', 'tone' => 'warn'];
                        } else {
                            $this->status = ['label' => 'Normal', 'tone' => 'ok'];
                        }
                    }
                }
                break;
            case 'sugar_level':
                if ($val !== null) {
                    $val = floatval($val);
                    if ($val >= 250) {
                        $this->status = ['label' => 'Critical', 'tone' => 'alert'];
                    } elseif ($val >= 180) {
                        $this->status = ['label' => 'High', 'tone' => 'warn'];
                    } elseif ($val >= 126) {
                        $this->status = ['label' => 'Elevated', 'tone' => 'warn'];
                    } elseif ($val < 70) {
                        $this->status = ['label' => 'Low', 'tone' => 'warn'];
                    } else {
                        $this->status = ['label' => 'Normal', 'tone' => 'ok'];
                    }
                }
                break;
            case 'temperature':
                if ($val !== null) {
                    $val = floatval($val);
                    if ($val >= 39.5) {
                        $this->status = ['label' => 'High Fever', 'tone' => 'alert'];
                    } elseif ($val >= 38.0) {
                        $this->status = ['label' => 'Fever', 'tone' => 'warn'];
                    } elseif ($val >= 37.3) {
                        $this->status = ['label' => 'Elevated', 'tone' => 'warn'];
                    } elseif ($val < 36.0) {
                        $this->status = ['label' => 'Low', 'tone' => 'warn'];
                    } else {
                        $this->status = ['label' => 'Normal', 'tone' => 'ok'];
                    }
                }
                break;
            case 'heart_rate':
                if ($val !== null) {
                    $val = floatval($val);
                    if ($val >= 150) {
                        $this->status = ['label' => 'Critical', 'tone' => 'alert'];
                    } elseif ($val >= 100) {
                        $this->status = ['label' => 'High', 'tone' => 'warn'];
                    } elseif ($val < 50) {
                        $this->status = ['label' => 'Low', 'tone' => 'warn'];
                    } elseif ($val < 60) {
                        $this->status = ['label' => 'Slow', 'tone' => 'warn'];
                    } else {
                        $this->status = ['label' => 'Normal', 'tone' => 'ok'];
                    }
                }
                break;
        }
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.vital-card');
    }
}
