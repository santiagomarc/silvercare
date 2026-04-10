<?php

namespace Tests\Unit;

use App\Http\Requests\UpdateMedicationRequest;
use App\Models\HealthMetric;
use App\Models\Medication;
use App\Models\Notification;
use App\Models\User;
use App\Models\UserProfile;
use App\Services\HealthAnalyticsService;
use App\Services\MedicationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\RequiredIf;
use Tests\TestCase;

class BackendOverhaulP0Test extends TestCase
{
    use RefreshDatabase;

    public function test_notification_for_elderly_scope_excludes_caregiver_refill_notifications(): void
    {
        $elderlyProfile = $this->createElderlyProfile();

        Notification::create([
            'elderly_id' => $elderlyProfile->id,
            'type' => Notification::TYPE_MEDICATION_REFILL_CAREGIVER,
            'title' => 'Refill needed',
            'message' => 'Should be hidden in elderly views.',
            'severity' => 'warning',
        ]);

        Notification::create([
            'elderly_id' => $elderlyProfile->id,
            'type' => 'caregiver_message',
            'title' => 'New message',
            'message' => 'Visible to elderly.',
            'severity' => 'reminder',
        ]);

        $visibleTypes = Notification::forElderly()
            ->where('elderly_id', $elderlyProfile->id)
            ->pluck('type')
            ->all();

        $this->assertSame(['caregiver_message'], $visibleTypes);
    }

    public function test_get_analytics_data_uses_single_health_metrics_select_query(): void
    {
        $elderlyProfile = $this->createElderlyProfile();

        foreach ([1, 3, 5] as $daysAgo) {
            HealthMetric::create([
                'elderly_id' => $elderlyProfile->id,
                'type' => 'heart_rate',
                'value' => 70 + $daysAgo,
                'unit' => 'bpm',
                'measured_at' => Carbon::now()->subDays($daysAgo),
                'source' => 'manual',
            ]);

            HealthMetric::create([
                'elderly_id' => $elderlyProfile->id,
                'type' => 'temperature',
                'value' => 36.5,
                'unit' => 'C',
                'measured_at' => Carbon::now()->subDays($daysAgo),
                'source' => 'manual',
            ]);
        }

        DB::flushQueryLog();
        DB::enableQueryLog();

        $analytics = app(HealthAnalyticsService::class)->getAnalyticsData(
            $elderlyProfile->id,
            [
                '7days' => Carbon::now()->subDays(7),
                '30days' => Carbon::now()->subDays(30),
                '90days' => Carbon::now()->subDays(90),
            ],
            ['heart_rate', 'temperature']
        );

        $healthMetricSelectQueries = collect(DB::getQueryLog())
            ->filter(function (array $query) {
                return preg_match('/\bselect\b/i', $query['query']) === 1
                    && preg_match('/from\s+["`]?health_metrics["`]?/i', $query['query']) === 1;
            });

        $this->assertCount(1, $healthMetricSelectQueries);
        $this->assertSame(3, $analytics['heart_rate']['7days']['count']);
        $this->assertSame(3, $analytics['temperature']['7days']['count']);
    }

    public function test_update_medication_request_and_service_support_partial_updates_without_schedule_loss(): void
    {
        $rules = (new UpdateMedicationRequest())->rules();

        $this->assertContains('sometimes', $rules['name']);
        $this->assertContains('sometimes', $rules['schedule_type']);
        $this->assertNotContains('sometimes', $rules['times_of_day']);

        $timesContainsRequiredIf = collect($rules['times_of_day'])
            ->contains(fn ($rule) => $rule instanceof RequiredIf);

        $this->assertTrue($timesContainsRequiredIf);

        [$caregiverProfile, $elderlyProfile] = $this->createLinkedCaregiverAndElderlyProfiles();

        $medication = Medication::create([
            'elderly_id' => $elderlyProfile->id,
            'caregiver_id' => $caregiverProfile->id,
            'name' => 'Vitamin C',
            'dosage' => '500',
            'dosage_unit' => 'mg',
            'frequency' => 'daily',
            'times_of_day' => ['08:00'],
            'start_date' => Carbon::today(),
            'track_inventory' => true,
            'current_stock' => 20,
            'low_stock_threshold' => 5,
            'is_active' => true,
        ]);

        $medication->schedules()->create([
            'schedule_type' => 'daily',
            'time_of_day' => '08:00',
        ]);

        app(MedicationService::class)->updateMedicationSchedule($medication, [
            'name' => 'Vitamin C Plus',
        ]);

        $this->assertDatabaseHas('medications', [
            'id' => $medication->id,
            'name' => 'Vitamin C Plus',
            'track_inventory' => true,
            'current_stock' => 20,
        ]);

        $this->assertSame(1, $medication->fresh()->schedules()->count());
    }

    private function createElderlyProfile(): UserProfile
    {
        $user = User::factory()->create();

        return UserProfile::create([
            'user_id' => $user->id,
            'user_type' => 'elderly',
            'username' => 'elderly-' . $user->id,
            'profile_completed' => true,
            'profile_skipped' => false,
        ]);
    }

    /**
     * @return array{0: UserProfile, 1: UserProfile}
     */
    private function createLinkedCaregiverAndElderlyProfiles(): array
    {
        $caregiverUser = User::factory()->create();
        $caregiverProfile = UserProfile::create([
            'user_id' => $caregiverUser->id,
            'user_type' => 'caregiver',
            'username' => 'caregiver-' . $caregiverUser->id,
            'profile_completed' => true,
            'profile_skipped' => false,
        ]);

        $elderlyUser = User::factory()->create();
        $elderlyProfile = UserProfile::create([
            'user_id' => $elderlyUser->id,
            'user_type' => 'elderly',
            'username' => 'elderly-linked-' . $elderlyUser->id,
            'caregiver_id' => $caregiverProfile->id,
            'profile_completed' => true,
            'profile_skipped' => false,
        ]);

        return [$caregiverProfile, $elderlyProfile];
    }
}
