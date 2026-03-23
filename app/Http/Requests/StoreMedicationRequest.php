<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMedicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->profile?->isCaregiver() === true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'dosage' => ['required', 'string', 'max:50'],
            'dosage_unit' => ['nullable', 'string', 'max:20'],
            'instructions' => ['nullable', 'string', 'max:1000'],
            'schedule_type' => ['required', Rule::in(['daily', 'weekly', 'specific_date'])],
            'days_of_week' => [Rule::requiredIf(fn () => $this->input('schedule_type') === 'weekly'), 'array', 'min:1'],
            'days_of_week.*' => ['string', 'in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday'],
            'times_of_day' => ['required', 'array', 'min:1'],
            'times_of_day.*' => ['string', 'date_format:H:i'],
            'specific_dates' => [Rule::requiredIf(fn () => $this->input('schedule_type') === 'specific_date'), 'array', 'min:1'],
            'specific_dates.*' => ['string', 'date_format:Y-m-d'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'current_stock' => ['nullable', 'integer', 'min:0'],
            'low_stock_threshold' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'days_of_week.required' => 'Weekly schedules require at least one day.',
            'specific_dates.required' => 'Specific-date schedules require at least one date.',
            'end_date.after_or_equal' => 'End date must be on or after the start date.',
        ];
    }
}