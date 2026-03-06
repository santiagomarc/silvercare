<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreChecklistRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->profile?->isCaregiver() === true;
    }

    public function rules(): array
    {
        return [
            'task' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'category' => ['required', 'string', 'max:50'],
            'due_date' => ['required', 'date'],
            'due_time' => ['nullable', 'date_format:H:i'],
            'priority' => ['nullable', 'string', 'in:low,medium,high'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'is_completed' => ['sometimes', 'boolean'],
        ];
    }
}