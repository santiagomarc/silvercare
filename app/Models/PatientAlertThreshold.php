<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientAlertThreshold extends Model
{
    use HasFactory;

    protected $fillable = [
        'elderly_id',
        'metric_type',
        'thresholds',
        'created_by',
    ];

    protected $casts = [
        'thresholds' => 'array',
    ];

    public function elderly(): BelongsTo
    {
        return $this->belongsTo(UserProfile::class, 'elderly_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
