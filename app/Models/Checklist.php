<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Checklist extends Model
{
    protected $fillable = [
        'elderly_id',
        'caregiver_id',
        'task',
        'description',
        'category',
        'due_date',
        'due_time',
        'priority',
        'notes',
        'frequency',
        'is_recurring',
        'is_completed',
        'completed_at',
    ];

    protected $casts = [
        'due_date' => 'date',
        'is_completed' => 'boolean',
        'completed_at' => 'datetime',
        'is_recurring' => 'boolean',
    ];

    public function elderly(): BelongsTo
    {
        return $this->belongsTo(UserProfile::class, 'elderly_id');
    }

    public function caregiver(): BelongsTo
    {
        return $this->belongsTo(UserProfile::class, 'caregiver_id');
    }
}
