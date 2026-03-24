<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class UpdateChecklistRequest extends StoreChecklistRequest
{
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
			'due_date' => ['required', 'date'],
			'due_time' => ['nullable', 'date_format:H:i'],
			'priority' => ['nullable', 'string', 'in:low,medium,high'],
			'notes' => ['nullable', 'string', 'max:1000'],
			'is_completed' => ['sometimes', 'boolean'],
		];
	}
}