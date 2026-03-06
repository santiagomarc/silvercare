<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMoodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->profile?->isElderly() === true;
    }

    public function rules(): array
    {
        return [
            'value' => ['required', 'integer', 'min:1', 'max:5'],
        ];
    }
}