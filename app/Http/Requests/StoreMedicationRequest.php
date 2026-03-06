<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
            'days_of_week' => ['required', 'array', 'min:1'],
            'days_of_week.*' => ['string', 'in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday'],
            'times_of_day' => ['required', 'array', 'min:1'],
            'times_of_day.*' => ['string', 'date_format:H:i'],
            'start_date' => ['nullable', 'date'],
            'current_stock' => ['nullable', 'integer', 'min:0'],
            'low_stock_threshold' => ['nullable', 'integer', 'min:0'],
        ];
    }
}