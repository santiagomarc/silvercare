<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreHealthMetricRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->profile?->isElderly() === true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'string', Rule::in(array_keys($this->vitalTypes()))],
            'value' => ['nullable', 'numeric'],
            'value_text' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $type = $this->string('type')->toString();
            $config = $this->vitalTypes()[$type] ?? null;

            if (!$config) {
                return;
            }

            if (($config['has_text_value'] ?? false) === true) {
                $valueText = $this->string('value_text')->trim()->toString();

                if ($valueText === '') {
                    $validator->errors()->add('value_text', 'Value is required for ' . $config['name'] . '.');
                }

                if ($type === 'blood_pressure' && $valueText !== '' && !preg_match('/^\d{2,3}\/\d{2,3}$/', $valueText)) {
                    $validator->errors()->add('value_text', 'Blood pressure must be in format like 120/80.');
                }

                // H3 FIX: Validate that BP components are within medically plausible ranges.
                if ($type === 'blood_pressure' && $valueText !== '' && preg_match('/^(\d{2,3})\/(\d{2,3})$/', $valueText, $bpMatch)) {
                    $systolic  = (int) $bpMatch[1];
                    $diastolic = (int) $bpMatch[2];

                    if ($systolic < 60 || $systolic > 250) {
                        $validator->errors()->add('value_text', 'Systolic pressure must be between 60 and 250 mmHg.');
                    }
                    if ($diastolic < 40 || $diastolic > 150) {
                        $validator->errors()->add('value_text', 'Diastolic pressure must be between 40 and 150 mmHg.');
                    }
                    if ($systolic > 0 && $diastolic > 0 && $systolic <= $diastolic) {
                        $validator->errors()->add('value_text', 'Systolic pressure must be higher than diastolic.');
                    }
                }

                return;
            }

            if (!$this->filled('value')) {
                $validator->errors()->add('value', 'Value is required for ' . $config['name'] . '.');
                return;
            }

            $value = (float) $this->input('value');
            $min = $config['min'] ?? 0;
            $max = $config['max'] ?? 999999;

            if ($value < $min || $value > $max) {
                $validator->errors()->add('value', "{$config['name']} must be between {$min} and {$max}.");
            }
        });
    }

    private function vitalTypes(): array
    {
        return collect(config('vitals'))
            ->except(['scorable_types', 'required_daily'])
            ->toArray();
    }
}