<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'category' => ['required', 'string', Rule::in([
                'Health',
                'Exercise',
                'Nutrition',
                'Social',
                'Hygiene',
                'Mental',
                'Medication',
                'Other',
            ])],
            'due_date' => ['required', 'date', 'after_or_equal:today'],
            'due_time' => ['nullable', 'date_format:H:i'],
            'priority' => ['nullable', 'string', 'in:low,medium,high'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'is_completed' => ['sometimes', 'boolean'],
        ];
    }
}