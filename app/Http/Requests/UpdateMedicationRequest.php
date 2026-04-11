<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class UpdateMedicationRequest extends StoreMedicationRequest
{
    public function rules(): array
    {
        $rules = parent::rules();

        $skipSometimes = ['days_of_week', 'specific_dates', 'times_of_day'];

        foreach ($rules as $field => $fieldRules) {
            if (in_array($field, $skipSometimes, true)) {
                continue;
            }

            $normalizedRules = is_array($fieldRules) ? $fieldRules : explode('|', $fieldRules);

            if (!in_array('sometimes', $normalizedRules, true)) {
                array_unshift($normalizedRules, 'sometimes');
            }

            $rules[$field] = $normalizedRules;
        }

        $rules['times_of_day'] = [
            Rule::requiredIf(fn () => $this->hasAny(['schedule_type', 'days_of_week', 'specific_dates', 'times_of_day'])),
            'array',
            'min:1',
        ];

        return $rules;
    }
}