<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProfileCompletionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'age' => ['nullable', 'integer', 'min:1', 'max:150'],
            'weight' => ['nullable', 'numeric', 'min:1', 'max:500'],
            'height' => ['nullable', 'numeric', 'min:1', 'max:300'],
            'emergency_name' => ['nullable', 'string', 'max:255'],
            'emergency_phone' => ['nullable', 'string', 'max:20'],
            'emergency_relationship' => ['nullable', 'string', 'max:255'],
            'conditions' => ['nullable', 'string'],
            'medications' => ['nullable', 'string'],
            'allergies' => ['nullable', 'string'],
        ];
    }
}
