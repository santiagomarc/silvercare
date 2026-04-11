<?php

namespace Tests\Feature;

use App\Models\Medication;
use App\Models\MedicationSchedule;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class MedicationStockAlertTest extends TestCase
{
    use RefreshDatabase;

    private function createLinkedProfiles(): array
    {
        $caregiverUser = User::factory()->create();
        $caregiverProfile = UserProfile::create([
            'user_id' => $caregiverUser->id,
            'user_type' => 'caregiver',
            'profile_completed' => true,
        ]);

        $elderlyUser = User::factory()->create();
        $elderlyProfile = UserProfile::create([
            'user_id' => $elderlyUser->id,
            'user_type' => 'elderly',
            'profile_completed' => true,
            'caregiver_id' => $caregiverProfile->id,
        ]);

        return [$elderlyUser, $elderlyProfile, $caregiverUser, $caregiverProfile];
    }

    public function test_low_stock_command_creates_notifications_for_elderly_and_caregiver(): void
    {
        [, $elderlyProfile] = $this->createLinkedProfiles();

        $medication = Medication::create([
            'elderly_id' => $elderlyProfile->id,
            'name' => 'Metformin',
            'is_active' => true,
            'track_inventory' => true,
            'current_stock' => 2,
        ]);

        MedicationSchedule::create([
            'medication_id' => $medication->id,
            'schedule_type' => 'daily',
            'time_of_day' => '08:00',
        ]);

        Artisan::call('medications:check-stock');

        $this->assertDatabaseHas('notifications', [
            'elderly_id' => $elderlyProfile->id,
            'type' => 'medication_refill',
        ]);

        $this->assertDatabaseHas('notifications', [
            'elderly_id' => $elderlyProfile->id,
            'type' => 'medication_refill_caregiver',
        ]);
    }

    public function test_low_stock_command_does_not_alert_when_stock_is_sufficient(): void
    {
        [, $elderlyProfile] = $this->createLinkedProfiles();

        $medication = Medication::create([
            'elderly_id' => $elderlyProfile->id,
            'name' => 'Vitamin C',
            'is_active' => true,
            'track_inventory' => true,
            'current_stock' => 40,
        ]);

        MedicationSchedule::create([
            'medication_id' => $medication->id,
            'schedule_type' => 'daily',
            'time_of_day' => '08:00',
        ]);

        Artisan::call('medications:check-stock');

        $this->assertDatabaseMissing('notifications', [
            'elderly_id' => $elderlyProfile->id,
            'type' => 'medication_refill',
        ]);

        $this->assertDatabaseMissing('notifications', [
            'elderly_id' => $elderlyProfile->id,
            'type' => 'medication_refill_caregiver',
        ]);
    }
}
