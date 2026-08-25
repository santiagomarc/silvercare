<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AlertDelivery extends Model
{
    use HasFactory;

    protected $fillable = [
        'alert_id',
        'recipient_profile_id',
        'channel',
        'state',
        'sent_at',
        'error',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    public function alert(): BelongsTo
    {
        return $this->belongsTo(Alert::class);
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(UserProfile::class, 'recipient_profile_id');
    }
}
