<?php

namespace Tests\Feature;

use App\Models\CareCheckin;
use App\Models\DoseInstance;
use App\Models\HealthMetric;
use App\Models\Medication;
use App\Models\OfflineSyncLog;
use App\Models\User;
use App\Models\UserProfile;
use App\Services\DoseInstanceGeneratorService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OfflineReconciliationTest extends TestCase
{
    use RefreshDatabase;

    private function createElderlyWithMedication(): array
    {
        $user = User::factory()->create(['name' => 'Alice Senior']);
        $profile = UserProfile::create([
            'user_id' => $user->id,
            'user_type' => 'elderly',
            'username' => 'alicesenior',
            'timezone' => 'Asia/Manila',
            'profile_completed' => true,
        ]);

        $medication = Medication::create([
            'elderly_id' => $profile->id,
            'name' => 'Amlodipine',
            'dosage' => '5mg',
            'times_of_day' => ['08:00'],
            'start_date' => Carbon::today('Asia/Manila')->toDateString(),
            'current_stock' => 20,
            'track_inventory' => true,
        ]);

        app(DoseInstanceGeneratorService::class)->generateForMedication($medication, Carbon::today('Asia/Manila'), 7);

        return [$user, $profile, $medication];
    }

    public function test_batch_offline_sync_applies_dose_vital_and_checkin(): void
    {
        [$user, $profile, $medication] = $this->createElderlyWithMedication();

        $mutations = [
            [
                'client_mutation_id' => 'mut_dose_101',
                'action_type' => 'confirm_dose',
                'payload' => [
                    'medication_id' => $medication->id,
                    'scheduled_time' => '08:00',
                ],
            ],
            [
                'client_mutation_id' => 'mut_vital_102',
                'action_type' => 'record_vital',
                'payload' => [
                    'type' => 'blood_pressure',
                    'value_text' => '122/80',
                    'unit' => 'mmHg',
                ],
            ],
            [
                'client_mutation_id' => 'mut_checkin_103',
                'action_type' => 'daily_checkin',
                'payload' => [
                    'status' => 'ok',
                    'notes' => 'Checked in from offline mode',
                ],
            ],
        ];

        $response = $this->actingAs($user)
            ->postJson(route('offline.sync'), [
                'mutations' => $mutations,
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'total_mutations' => 3,
                'applied_count' => 3,
                'conflict_count' => 0,
            ]);

        $this->assertDatabaseHas('offline_sync_logs', [
            'client_mutation_id' => 'mut_dose_101',
            'status' => 'applied',
        ]);

        $this->assertDatabaseHas('health_metrics', [
            'elderly_id' => $profile->id,
            'type' => 'blood_pressure',
            'value_text' => '122/80',
            'source' => 'offline_sync',
        ]);

        $this->assertTrue($profile->fresh()->hasCheckedInToday());
    }

    public function test_offline_sync_is_idempotent_for_duplicate_mutation_ids(): void
    {
        [$user, $profile, $medication] = $this->createElderlyWithMedication();

        $mutations = [
            [
                'client_mutation_id' => 'mut_vital_unique_999',
                'action_type' => 'record_vital',
                'payload' => [
                    'type' => 'sugar_level',
                    'value' => 110.0,
                    'unit' => 'mg/dL',
                ],
            ],
        ];

        // First sync
        $res1 = $this->actingAs($user)->postJson(route('offline.sync'), ['mutations' => $mutations]);
        $res1->assertOk()->assertJson(['applied_count' => 1]);

        // Duplicate sync
        $res2 = $this->actingAs($user)->postJson(route('offline.sync'), ['mutations' => $mutations]);
        $res2->assertOk();

        // Ensure HealthMetric was created only once
        $this->assertEquals(1, HealthMetric::where('elderly_id', $profile->id)->where('type', 'sugar_level')->count());
    }

    public function test_offline_sync_status_endpoint(): void
    {
        [$user, $profile] = $this->createElderlyWithMedication();

        OfflineSyncLog::create([
            'elderly_id' => $profile->id,
            'client_mutation_id' => 'mut_log_555',
            'action_type' => 'daily_checkin',
            'payload' => ['status' => 'ok'],
            'status' => 'applied',
            'applied_at' => Carbon::now(),
        ]);

        $res = $this->actingAs($user)->getJson(route('offline.status'));

        $res->assertOk()
            ->assertJson([
                'success' => true,
                'total_applied' => 1,
                'total_conflicts' => 0,
            ])
            ->assertJsonStructure(['recent_logs']);
    }
}
