<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LinkCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'caregiver_profile_id',
        'used_by_profile_id',
        'expires_at',
        'used_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
    ];

    public function caregiverProfile(): BelongsTo
    {
        return $this->belongsTo(UserProfile::class, 'caregiver_profile_id');
    }

    public function usedByProfile(): BelongsTo
    {
        return $this->belongsTo(UserProfile::class, 'used_by_profile_id');
    }

    public function isActive(): bool
    {
        return $this->used_at === null && $this->expires_at->isFuture();
    }
}
