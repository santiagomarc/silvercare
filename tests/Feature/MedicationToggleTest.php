<?php

namespace Tests\Feature;

use App\Models\Medication;
use App\Models\MedicationLog;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class MedicationToggleTest extends TestCase
{
    use RefreshDatabase;

    public function test_medication_can_be_taken_and_then_immediately_unmarked()
    {
        $user = User::factory()->create();
        $profile = UserProfile::create([
            'user_id' => $user->id,
            'user_type' => 'elderly', 'profile_completed' => true,
        ]);

        $medication = Medication::create([
            'elderly_id' => $profile->id,
            'name' => 'Test Med',
            'dosage' => '10mg',
            'start_date' => Carbon::today(),
            'frequency' => 'daily',
            'times_of_day' => ['09:00'],
        ]);

        // Set time to be exactly at 09:00
        Carbon::setTestNow(Carbon::today()->setHour(9)->setMinute(0));

        $this->actingAs($user);

        // 1. Take medication
        $response = $this->postJson(route('elderly.medications.take', $medication), [
            'time' => '09:00'
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('medication_logs', [
            'medication_id' => $medication->id,
            'is_taken' => true,
        ]);

        // 2. Undo medication (immediately)
        $response = $this->postJson(route('elderly.medications.undo', $medication), [
            'time' => '09:00'
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseMissing('medication_logs', [
            'medication_id' => $medication->id,
        ]);

        // 3. Take again (relog)
        $response = $this->postJson(route('elderly.medications.take', $medication), [
            'time' => '09:00'
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('medication_logs', [
            'medication_id' => $medication->id,
            'is_taken' => true,
        ]);
        
        Carbon::setTestNow(); // Reset
    }

    public function test_medication_cannot_be_unmarked_after_grace_period()
    {
        $user = User::factory()->create();
        $profile = UserProfile::create([
            'user_id' => $user->id,
            'user_type' => 'elderly', 'profile_completed' => true,
        ]);

        $medication = Medication::create([
            'elderly_id' => $profile->id,
            'name' => 'Test Med',
            'dosage' => '10mg',
            'start_date' => Carbon::today(),
            'frequency' => 'daily',
            'times_of_day' => ['09:00'],
        ]);

        // Taken at 09:00
        Carbon::setTestNow(Carbon::today()->setHour(9)->setMinute(0));
        MedicationLog::create([
            'elderly_id' => $profile->id,
            'medication_id' => $medication->id,
            'scheduled_time' => Carbon::today()->setHour(9)->setMinute(0),
            'is_taken' => true,
            'taken_at' => Carbon::now(),
        ]);

        // Try to undo at 10:05 (65 mins later - past 60 min grace)
        Carbon::setTestNow(Carbon::today()->setHour(10)->setMinute(5));

        $this->actingAs($user);
        $response = $this->postJson(route('elderly.medications.undo', $medication), [
            'time' => '09:00'
        ]);

        $response->assertStatus(400);
        $response->assertJson(['message' => 'Cannot unmark - grace period has ended']);
        
        Carbon::setTestNow(); // Reset
    }
}
