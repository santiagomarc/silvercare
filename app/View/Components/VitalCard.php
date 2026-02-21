<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class VitalCard extends Component
{
    public string $type;
    public ?array $data;
    public string $title;
    public string $unit;
    public string $icon;
    public string $color;
    public string $bg;
    public string $border;
    public string $route;
    public ?array $status = null;

    /**
     * Create a new component instance.
     */
    public function __construct(string $type, ?array $data = null)
    {
        $this->type = $type;
        $this->data = $data ?? ['recorded' => false];
        
        $this->setupVitalConfig();
        $this->calculateStatus();
    }

    private function setupVitalConfig(): void
    {
        switch ($this->type) {
            case 'blood_pressure':
                $this->title = 'Blood Pressure';
                $this->unit = 'mmHg';
                $this->color = 'text-red-500';
                $this->bg = 'bg-red-50';
                $this->border = 'border-red-400';
                $this->route = route('elderly.vitals.blood_pressure');
                $this->icon = '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path></svg>';
                break;
            case 'sugar_level':
                $this->title = 'Sugar Level';
                $this->unit = 'mg/dL';
                $this->color = 'text-blue-500';
                $this->bg = 'bg-blue-50';
                $this->border = 'border-blue-400';
                $this->route = route('elderly.vitals.sugar_level');
                $this->icon = '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path></svg>';
                break;
            case 'temperature':
                $this->title = 'Temperature';
                $this->unit = '°C';
                $this->color = 'text-orange-500';
                $this->bg = 'bg-orange-50';
                $this->border = 'border-orange-400';
                $this->route = route('elderly.vitals.temperature');
                $this->icon = '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>';
                break;
            case 'heart_rate':
                $this->title = 'Heart Rate';
                $this->unit = 'bpm';
                $this->color = 'text-rose-500';
                $this->bg = 'bg-rose-50';
                $this->border = 'border-rose-400';
                $this->route = route('elderly.vitals.heart_rate');
                $this->icon = '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>';
                break;
        }
    }

    private function calculateStatus(): void
    {
        if (!($this->data['recorded'] ?? false)) {
            return;
        }

        $val = $this->data['value'] ?? null;
        $valText = $this->data['value_text'] ?? null;

        switch ($this->type) {
            case 'blood_pressure':
                if ($valText) {
                    $parts = explode('/', $valText);
                    if (count($parts) === 2) {
                        $sys = intval($parts[0]);
                        $dia = intval($parts[1]);
                        if ($sys >= 180 || $dia >= 120) {
                            $this->status = ['label' => 'Critical', 'bg' => 'bg-red-500', 'text' => 'text-white'];
                        } elseif ($sys >= 140 || $dia >= 90) {
                            $this->status = ['label' => 'High', 'bg' => 'bg-orange-100', 'text' => 'text-orange-700'];
                        } elseif ($sys >= 130 || $dia >= 80) {
                            $this->status = ['label' => 'Elevated', 'bg' => 'bg-yellow-100', 'text' => 'text-yellow-700'];
                        } elseif ($sys < 90 || $dia < 60) {
                            $this->status = ['label' => 'Low', 'bg' => 'bg-blue-100', 'text' => 'text-blue-700'];
                        } else {
                            $this->status = ['label' => 'Normal', 'bg' => 'bg-green-100', 'text' => 'text-green-700'];
                        }
                    }
                }
                break;
            case 'sugar_level':
                if ($val !== null) {
                    $val = floatval($val);
                    if ($val >= 250) {
                        $this->status = ['label' => 'Critical', 'bg' => 'bg-red-500', 'text' => 'text-white'];
                    } elseif ($val >= 180) {
                        $this->status = ['label' => 'High', 'bg' => 'bg-orange-100', 'text' => 'text-orange-700'];
                    } elseif ($val >= 126) {
                        $this->status = ['label' => 'Elevated', 'bg' => 'bg-yellow-100', 'text' => 'text-yellow-700'];
                    } elseif ($val < 70) {
                        $this->status = ['label' => 'Low', 'bg' => 'bg-blue-100', 'text' => 'text-blue-700'];
                    } else {
                        $this->status = ['label' => 'Normal', 'bg' => 'bg-green-100', 'text' => 'text-green-700'];
                    }
                }
                break;
            case 'temperature':
                if ($val !== null) {
                    $val = floatval($val);
                    if ($val >= 39.5) {
                        $this->status = ['label' => 'High Fever', 'bg' => 'bg-red-500', 'text' => 'text-white'];
                    } elseif ($val >= 38.0) {
                        $this->status = ['label' => 'Fever', 'bg' => 'bg-orange-100', 'text' => 'text-orange-700'];
                    } elseif ($val >= 37.3) {
                        $this->status = ['label' => 'Elevated', 'bg' => 'bg-yellow-100', 'text' => 'text-yellow-700'];
                    } elseif ($val < 36.0) {
                        $this->status = ['label' => 'Low', 'bg' => 'bg-blue-100', 'text' => 'text-blue-700'];
                    } else {
                        $this->status = ['label' => 'Normal', 'bg' => 'bg-green-100', 'text' => 'text-green-700'];
                    }
                }
                break;
            case 'heart_rate':
                if ($val !== null) {
                    $val = floatval($val);
                    if ($val >= 150) {
                        $this->status = ['label' => 'Critical', 'bg' => 'bg-red-500', 'text' => 'text-white'];
                    } elseif ($val >= 100) {
                        $this->status = ['label' => 'High', 'bg' => 'bg-orange-100', 'text' => 'text-orange-700'];
                    } elseif ($val < 50) {
                        $this->status = ['label' => 'Low', 'bg' => 'bg-blue-100', 'text' => 'text-blue-700'];
                    } elseif ($val < 60) {
                        $this->status = ['label' => 'Slow', 'bg' => 'bg-yellow-100', 'text' => 'text-yellow-700'];
                    } else {
                        $this->status = ['label' => 'Normal', 'bg' => 'bg-green-100', 'text' => 'text-green-700'];
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
