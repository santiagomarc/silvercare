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
            'age' => ['required', 'integer', 'min:1', 'max:150'],
            'weight' => ['required', 'numeric', 'min:1', 'max:500'],
            'height' => ['required', 'numeric', 'min:1', 'max:300'],
            'emergency_name' => ['required', 'string', 'max:255'],
            'emergency_phone' => ['required', 'string', 'max:20'],
            'emergency_relationship' => ['required', 'string', 'in:Spouse (Asawa),Child (Anak),Family/Relative (Pamilya/Kamag-anak),Friend (Kaibigan),Neighbor (Kapitbahay)'],
            'conditions' => ['required', 'string'],
            'medications' => ['required', 'string'],
            'allergies' => ['required', 'string'],
        ];
    }
}
