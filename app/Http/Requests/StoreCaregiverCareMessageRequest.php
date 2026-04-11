<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCaregiverCareMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->profile?->isCaregiver() === true;
    }

    public function rules(): array
    {
        return [
            'elderly_id' => ['required', 'integer', 'exists:user_profiles,id'],
            'message' => ['required', 'string', 'max:1200'],
        ];
    }
}
