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