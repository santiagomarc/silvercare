<?php

namespace Tests\Feature;

use App\Models\DoseInstance;
use App\Models\Medication;
use App\Models\MedicationLog;
use App\Models\MedicationSchedule;
use App\Models\User;
use App\Models\UserProfile;
use App\Services\AiAssistantService;
use App\Services\DoseAdministrationService;
use App\Services\DoseInstanceGeneratorService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DoseAdministrationTest extends TestCase
{
    use RefreshDatabase;

    private function createElderlyUser(): array
    {
        $user = User::factory()->create(['name' => 'John Senior', 'email' => 'senior@example.com']);
        $profile = UserProfile::create([
            'user_id' => $user->id,
            'user_type' => 'elderly',
            'username' => 'johnsenior',
            'timezone' => 'Asia/Manila',
            'profile_completed' => true,
        ]);

        return [$user, $profile];
    }

    private function createMedication(UserProfile $profile, array $attributes = []): Medication
    {
        $medication = Medication::create(array_merge([
            'elderly_id' => $profile->id,
            'name' => 'Amlodipine',
            'dosage' => '5mg',
            'dosage_unit' => 'tablet',
            'frequency' => 'daily',
            'times_of_day' => ['08:00', '20:00'],
            'is_active' => true,
            'track_inventory' => true,
            'current_stock' => 30,
            'low_stock_threshold' => 5,
        ], $attributes));

        MedicationSchedule::create([
            'medication_id' => $medication->id,
            'schedule_type' => 'daily',
            'time_of_day' => '08:00',
        ]);

        MedicationSchedule::create([
            'medication_id' => $medication->id,
            'schedule_type' => 'daily',
            'time_of_day' => '20:00',
        ]);

        return $medication;
    }

    public function test_dose_can_be_confirmed_and_decrements_inventory_once(): void
    {
        [$user, $profile] = $this->createElderlyUser();
        $medication = $this->createMedication($profile, ['current_stock' => 10]);

        Carbon::setTestNow(Carbon::parse('2026-08-24 08:15:00', 'Asia/Manila'));

        $response = $this->actingAs($user)
            ->postJson(route('elderly.medications.take', $medication), [
                'time' => '08:00',
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'is_taken' => true,
                'taken_late' => false,
                'status' => 'taken',
            ]);

        $this->assertEquals(9, $medication->fresh()->current_stock);

        $this->assertDatabaseHas('medication_dose_instances', [
            'elderly_id' => $profile->id,
            'medication_id' => $medication->id,
            'state' => 'taken',
        ]);

        $this->assertDatabaseHas('medication_logs', [
            'elderly_id' => $profile->id,
            'medication_id' => $medication->id,
            'is_taken' => true,
        ]);
    }

    public function test_idempotent_duplicate_confirm_does_not_double_decrement_inventory(): void
    {
        [$user, $profile] = $this->createElderlyUser();
        $medication = $this->createMedication($profile, ['current_stock' => 10]);

        Carbon::setTestNow(Carbon::parse('2026-08-24 08:15:00', 'Asia/Manila'));

        // First confirm
        $res1 = $this->actingAs($user)
            ->withHeader('X-Idempotency-Key', 'req-unique-123')
            ->postJson(route('elderly.medications.take', $medication), [
                'time' => '08:00',
            ]);
        $res1->assertOk();
        $this->assertEquals(9, $medication->fresh()->current_stock);

        // Immediate retry with same key / parameters
        $res2 = $this->actingAs($user)
            ->withHeader('X-Idempotency-Key', 'req-unique-123')
            ->postJson(route('elderly.medications.take', $medication), [
                'time' => '08:00',
            ]);

        $res2->assertOk()
            ->assertJson([
                'success' => true,
                'is_taken' => true,
                'message' => 'Medication already marked as taken.',
            ]);

        // Stock MUST remain 9, not decremented to 8!
        $this->assertEquals(9, $medication->fresh()->current_stock);
    }

    public function test_cannot_confirm_dose_before_window_starts(): void
    {
        [$user, $profile] = $this->createElderlyUser();
        $medication = $this->createMedication($profile);

        // 07:45 is before 08:00 window start
        Carbon::setTestNow(Carbon::parse('2026-08-24 07:45:00', 'Asia/Manila'));

        $response = $this->actingAs($user)
            ->postJson(route('elderly.medications.take', $medication), [
                'time' => '08:00',
            ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'error_code' => 'DOSE_TOO_EARLY',
            ]);
    }

    public function test_late_dose_marks_state_as_taken_late(): void
    {
        [$user, $profile] = $this->createElderlyUser();
        $medication = $this->createMedication($profile);

        // 09:30 is past 08:00 + 60m grace period
        Carbon::setTestNow(Carbon::parse('2026-08-24 09:30:00', 'Asia/Manila'));

        $response = $this->actingAs($user)
            ->postJson(route('elderly.medications.take', $medication), [
                'time' => '08:00',
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'is_taken' => true,
                'taken_late' => true,
                'status' => 'taken_late',
            ]);

        $this->assertDatabaseHas('medication_dose_instances', [
            'elderly_id' => $profile->id,
            'medication_id' => $medication->id,
            'state' => 'taken_late',
        ]);
    }

    public function test_cannot_take_expired_medication(): void
    {
        [$user, $profile] = $this->createElderlyUser();
        $medication = $this->createMedication($profile, [
            'start_date' => '2026-01-01',
            'end_date' => '2026-08-01', // Expired in the past
        ]);

        Carbon::setTestNow(Carbon::parse('2026-08-24 08:15:00', 'Asia/Manila'));

        $response = $this->actingAs($user)
            ->postJson(route('elderly.medications.take', $medication), [
                'time' => '08:00',
            ]);

        $response->assertStatus(422);
    }

    public function test_cannot_take_unscheduled_time(): void
    {
        [$user, $profile] = $this->createElderlyUser();
        $medication = $this->createMedication($profile);

        Carbon::setTestNow(Carbon::parse('2026-08-24 14:00:00', 'Asia/Manila'));

        $response = $this->actingAs($user)
            ->postJson(route('elderly.medications.take', $medication), [
                'time' => '14:00', // Only 08:00 and 20:00 scheduled
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'error_code' => 'UNSCHEDULED_DOSE',
            ]);
    }

    public function test_undo_dose_within_grace_period_restores_inventory(): void
    {
        [$user, $profile] = $this->createElderlyUser();
        $medication = $this->createMedication($profile, ['current_stock' => 10]);

        Carbon::setTestNow(Carbon::parse('2026-08-24 08:15:00', 'Asia/Manila'));

        // Take dose
        $this->actingAs($user)
            ->postJson(route('elderly.medications.take', $medication), ['time' => '08:00'])
            ->assertOk();

        $this->assertEquals(9, $medication->fresh()->current_stock);

        // Undo dose at 08:30 (within grace period)
        Carbon::setTestNow(Carbon::parse('2026-08-24 08:30:00', 'Asia/Manila'));

        $undoRes = $this->actingAs($user)
            ->postJson(route('elderly.medications.undo', $medication), ['time' => '08:00']);

        $undoRes->assertOk()
            ->assertJson([
                'success' => true,
                'is_taken' => false,
            ]);

        $this->assertEquals(10, $medication->fresh()->current_stock);
        $this->assertDatabaseHas('medication_dose_instances', [
            'elderly_id' => $profile->id,
            'medication_id' => $medication->id,
            'state' => 'pending',
        ]);
    }

    public function test_cannot_undo_dose_after_grace_period(): void
    {
        [$user, $profile] = $this->createElderlyUser();
        $medication = $this->createMedication($profile);

        Carbon::setTestNow(Carbon::parse('2026-08-24 08:15:00', 'Asia/Manila'));

        $this->actingAs($user)
            ->postJson(route('elderly.medications.take', $medication), ['time' => '08:00'])
            ->assertOk();

        // Attempt undo at 09:30 (past grace period)
        Carbon::setTestNow(Carbon::parse('2026-08-24 09:30:00', 'Asia/Manila'));

        $undoRes = $this->actingAs($user)
            ->postJson(route('elderly.medications.undo', $medication), ['time' => '08:00']);

        $undoRes->assertStatus(400)
            ->assertJson([
                'success' => false,
                'error_code' => 'UNDO_WINDOW_EXPIRED',
            ]);
    }

    public function test_dose_instance_generator_creates_7_day_horizon(): void
    {
        [$user, $profile] = $this->createElderlyUser();
        $medication = $this->createMedication($profile);

        $generator = app(DoseInstanceGeneratorService::class);
        $start = Carbon::parse('2026-08-24 00:00:00', 'Asia/Manila');

        $count = $generator->generateForActiveMedications($start, 7);

        // 2 doses per day * 8 days (inclusive) = 16 instances
        $this->assertGreaterThanOrEqual(14, $count);

        $this->assertDatabaseHas('medication_dose_instances', [
            'elderly_id' => $profile->id,
            'medication_id' => $medication->id,
            'local_date' => '2026-08-24',
            'state' => 'pending',
        ]);
    }

    public function test_dose_instance_confirm_api_endpoint_with_idempotency_key(): void
    {
        [$user, $profile] = $this->createElderlyUser();
        $medication = $this->createMedication($profile, ['current_stock' => 10]);

        Carbon::setTestNow(Carbon::parse('2026-08-24 08:15:00', 'Asia/Manila'));

        $generator = app(DoseInstanceGeneratorService::class);
        $generator->generateForMedication($medication, Carbon::today('Asia/Manila'), 1);

        $instance = DoseInstance::where('elderly_id', $profile->id)
            ->where('medication_id', $medication->id)
            ->firstOrFail();

        $response = $this->actingAs($user)
            ->withHeader('X-Idempotency-Key', 'api-idempotency-999')
            ->postJson(route('doses.confirm', $instance));

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'is_taken' => true,
                'status' => 'taken',
            ]);

        $this->assertEquals(9, $medication->fresh()->current_stock);
        $this->assertEquals('api-idempotency-999', $instance->fresh()->idempotency_key);
    }
}
