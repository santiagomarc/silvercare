<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CareMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'caregiver_id',
        'elderly_id',
        'sender_profile_id',
        'message',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function caregiver(): BelongsTo
    {
        return $this->belongsTo(UserProfile::class, 'caregiver_id');
    }

    public function elderly(): BelongsTo
    {
        return $this->belongsTo(UserProfile::class, 'elderly_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(UserProfile::class, 'sender_profile_id');
    }

    public function isFromCaregiver(): bool
    {
        return $this->sender_profile_id === $this->caregiver_id;
    }
}
