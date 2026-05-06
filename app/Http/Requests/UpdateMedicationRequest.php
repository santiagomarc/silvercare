<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class UpdateMedicationRequest extends StoreMedicationRequest
{
    public function rules(): array
    {
        $rules = parent::rules();

        // C10 FIX: 'days_of_week' is intentionally excluded from $skipSometimes.
        // If schedule_type=weekly is submitted in an update, the parent's
        // requiredIf rule MUST still enforce at least one day is selected.
        // Only fields that are fully optional during partial updates go here.
        $skipSometimes = ['specific_dates', 'times_of_day'];

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

        // times_of_day: required only when any scheduling field is being updated.
        $rules['times_of_day'] = [
            Rule::requiredIf(fn () => $this->hasAny(['schedule_type', 'days_of_week', 'specific_dates', 'times_of_day'])),
            'array',
            'min:1',
        ];

        // specific_dates: present only when schedule_type=specific_date is submitted.
        $rules['specific_dates'] = [
            'sometimes',
            Rule::requiredIf(fn () => $this->input('schedule_type') === 'specific_date'),
            'array',
            'min:1',
        ];

        return $rules;
    }
}