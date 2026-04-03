<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserProfile extends Model
{
    use HasFactory;

    /**
     * MASS ASSIGNMENT
     * All fields that can be saved by the controller.
     */
    protected $fillable = [
        'user_id',
        'user_type',
        'username',
        'profile_photo',
        'phone_number',
        'sex',
        'age',
        'height',
        'weight',
        'address',
        'medical_conditions',
        'medications',
        'allergies',
        'emergency_name',
        'emergency_phone',
        'emergency_relationship',
        // Legacy fields maintained to prevent errors
        'emergency_contact',
        'medical_info',
        'relationship',
        'caregiver_id',
        'profile_completed',
        'profile_skipped',
        'is_active',
        'last_login_at',
    ];

    /**
     * CASTING
     * Automatically convert JSON columns to PHP Arrays.
     */
    protected $casts = [
        'medical_conditions' => 'array',
        'medications'        => 'array',
        'allergies'          => 'array',
        'emergency_contact'  => 'array',
        'medical_info'       => 'array',
        'profile_completed'  => 'boolean',
        'profile_skipped'    => 'boolean',
        'is_active'          => 'boolean',
        'last_login_at'      => 'datetime',
    ];

    // --- RELATIONSHIPS (PRESERVED) ---

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function trackedMedications(): HasMany
    {
        return $this->hasMany(Medication::class, 'elderly_id');
    }

    public function medicationLogs(): HasMany
    {
        return $this->hasMany(MedicationLog::class, 'elderly_id');
    }

    public function healthMetrics(): HasMany
    {
        return $this->hasMany(HealthMetric::class, 'elderly_id');
    }

    public function checklists(): HasMany
    {
        return $this->hasMany(Checklist::class, 'elderly_id');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class, 'elderly_id');
    }

    public function caregiver(): BelongsTo
    {
        return $this->belongsTo(UserProfile::class, 'caregiver_id');
    }

    /**
     * All elderly patients assigned to this caregiver.
     * Changed from HasOne → HasMany to support multi-patient caregivers.
     *
     * NOTE: The caregiver dashboard currently calls ->elderly (as a HasOne property).
     * Until that dashboard is refactored, use ->elderly()->first() for backwards
     * compatibility, or access via $caregiver->elderlyPatients->first().
     */
    public function elderlyPatients(): HasMany
    {
        return $this->hasMany(UserProfile::class, 'caregiver_id');
    }

    /**
     * @deprecated Use elderlyPatients() for multi-patient support.
     * Kept temporarily so the caregiver dashboard does not break before its refactor.
     */
    public function elderly(): HasMany
    {
        return $this->hasMany(UserProfile::class, 'caregiver_id');
    }

    public function isElderly(): bool
    {
        return $this->user_type === 'elderly';
    }

    public function isCaregiver(): bool
    {
        return $this->user_type === 'caregiver';
    }
}