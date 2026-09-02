<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PushSubscription extends Model
{
    protected $fillable = [
        'profile_id',
        'endpoint',
        'endpoint_hash',
        'p256dh_key',
        'auth_token',
        'user_agent',
        'last_used_at',
    ];

    protected $casts = [
        'last_used_at' => 'datetime',
    ];

    /**
     * The push endpoint is a long URL, so it is indexed by hash rather than by
     * value. Keep the two in lockstep through this helper.
     */
    public static function hashEndpoint(string $endpoint): string
    {
        return hash('sha256', $endpoint);
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(UserProfile::class, 'profile_id');
    }

    /**
     * Shape expected by minishlink/web-push.
     *
     * @return array{endpoint: string, publicKey: string, authToken: string, contentEncoding: string}
     */
    public function toWebPushArray(): array
    {
        return [
            'endpoint' => $this->endpoint,
            'publicKey' => $this->p256dh_key,
            'authToken' => $this->auth_token,
            'contentEncoding' => 'aesgcm',
        ];
    }
}
