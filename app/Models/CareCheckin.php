<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CareCheckin extends Model
{
    use HasFactory;

    protected $fillable = [
        'elderly_id',
        'checkin_date',
        'status',
        'notes',
        'mood',
        'checked_in_at',
        'source',
    ];

    protected $casts = [
        'checkin_date' => 'date',
        'checked_in_at' => 'datetime',
    ];

    public function elderly(): BelongsTo
    {
        return $this->belongsTo(UserProfile::class, 'elderly_id');
    }

    // Scopes
    public function scopeToday(Builder $query): Builder
    {
        return $query->where('checkin_date', Carbon::today()->toDateString());
    }

    public function scopeOk(Builder $query): Builder
    {
        return $query->where('status', 'ok');
    }

    public function scopeNeedHelp(Builder $query): Builder
    {
        return $query->where('status', 'need_help');
    }

    public function scopeMissed(Builder $query): Builder
    {
        return $query->where('status', 'missed');
    }

    // Helpers
    public function isOk(): bool
    {
        return $this->status === 'ok';
    }

    public function needsHelp(): bool
    {
        return $this->status === 'need_help';
    }
}
