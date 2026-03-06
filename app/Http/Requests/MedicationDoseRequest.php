<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MedicationDoseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->profile?->isElderly() === true;
    }

    public function rules(): array
    {
        return [
            'time' => ['required', 'date_format:H:i'],
        ];
    }
}