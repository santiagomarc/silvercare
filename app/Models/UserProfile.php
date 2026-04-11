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

    public function careMessagesAsCaregiver(): HasMany
    {
        return $this->hasMany(CareMessage::class, 'caregiver_id');
    }

    public function careMessagesAsElderly(): HasMany
    {
        return $this->hasMany(CareMessage::class, 'elderly_id');
    }

    public function sentCareMessages(): HasMany
    {
        return $this->hasMany(CareMessage::class, 'sender_profile_id');
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

    /**
     * Resolve normalized medical conditions, including legacy medical_info fallback.
     *
     * @return array<int, string>
     */
    public function resolvedMedicalConditions(): array
    {
        return $this->resolveMedicalList('medical_conditions', 'conditions');
    }

    /**
     * Resolve normalized medications list, including legacy medical_info fallback.
     *
     * @return array<int, string>
     */
    public function resolvedMedications(): array
    {
        return $this->resolveMedicalList('medications', 'medications');
    }

    /**
     * Resolve normalized allergies list, including legacy medical_info fallback.
     *
     * @return array<int, string>
     */
    public function resolvedAllergies(): array
    {
        return $this->resolveMedicalList('allergies', 'allergies');
    }

    /**
     * @return array<int, string>
     */
    private function resolveMedicalList(string $attribute, string $legacyKey): array
    {
        $primary = $this->normalizeStringList($this->getAttribute($attribute));
        if (!empty($primary)) {
            return $primary;
        }

        $legacyInfo = $this->getAttribute('medical_info');
        if (is_string($legacyInfo)) {
            $legacyInfo = json_decode($legacyInfo, true) ?? [];
        }

        if (!is_array($legacyInfo)) {
            return [];
        }

        return $this->normalizeStringList($legacyInfo[$legacyKey] ?? []);
    }

    /**
     * Normalize arrays/JSON/CSV values into a clean string list.
     *
     * @return array<int, string>
     */
    private function normalizeStringList(mixed $value): array
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                $value = $decoded;
            } else {
                $value = array_map('trim', explode(',', $value));
            }
        }

        if (!is_array($value)) {
            return [];
        }

        return collect($value)
            ->map(fn ($item) => is_scalar($item) ? trim((string) $item) : '')
            ->filter(fn ($item) => $item !== '')
            ->values()
            ->all();
    }
}