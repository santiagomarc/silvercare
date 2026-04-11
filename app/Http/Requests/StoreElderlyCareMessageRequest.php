<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreElderlyCareMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->profile?->isElderly() === true;
    }

    public function rules(): array
    {
        return [
            'message' => ['required', 'string', 'max:1200'],
        ];
    }
}
