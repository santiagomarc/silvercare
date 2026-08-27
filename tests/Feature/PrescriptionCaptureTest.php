<?php

namespace Tests\Feature;

use App\Models\CaptureSession;
use App\Models\Medication;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PrescriptionCaptureTest extends TestCase
{
    use RefreshDatabase;

    private function createLinkedPair(): array
    {
        $caregiverUser = User::factory()->create(['name' => 'Dr. Caregiver']);
        $caregiverProfile = UserProfile::create([
            'user_id' => $caregiverUser->id,
            'user_type' => 'caregiver',
            'username' => 'drcaregiver',
            'profile_completed' => true,
        ]);

        $elderlyUser = User::factory()->create(['name' => 'Alice Senior']);
        $elderlyProfile = UserProfile::create([
            'user_id' => $elderlyUser->id,
            'user_type' => 'elderly',
            'username' => 'alicesenior',
            'caregiver_id' => $caregiverProfile->id,
            'profile_completed' => true,
        ]);

        return [$elderlyUser, $elderlyProfile, $caregiverUser, $caregiverProfile];
    }

    public function test_upload_and_confirm_prescription_scan(): void
    {
        Storage::fake('public');
        [$elderlyUser, $elderlyProfile, $caregiverUser, $caregiverProfile] = $this->createLinkedPair();

        $fakeImage = UploadedFile::fake()->image('prescription.jpg', 600, 400);

        // 1. Upload photo
        $uploadRes = $this->actingAs($caregiverUser)
            ->postJson(route('capture.upload'), [
                'image' => $fakeImage,
                'session_type' => 'prescription_scan',
                'elderly_id' => $elderlyProfile->id,
            ]);

        $uploadRes->assertOk()
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure(['session' => ['id', 'status', 'extracted_data']]);

        $sessionId = $uploadRes->json('session.id');
        $session = CaptureSession::findOrFail($sessionId);

        // 2. Confirm prescription details
        $confirmRes = $this->actingAs($caregiverUser)
            ->postJson(route('capture.confirm', $session), [
                'confirmed_data' => [
                    'name' => 'Amlodipine',
                    'dosage' => '5mg',
                    'instructions' => 'Take 1 tablet daily in the morning',
                    'schedule_type' => 'daily',
                    'schedule_times' => ['08:00'],
                    'current_stock' => 30,
                ],
            ]);

        $confirmRes->assertOk()
            ->assertJson([
                'success' => true,
                'type' => 'medication',
            ]);

        $this->assertDatabaseHas('medications', [
            'elderly_id' => $elderlyProfile->id,
            'name' => 'Amlodipine',
            'dosage' => '5mg',
        ]);

        $this->assertTrue($session->fresh()->isConfirmed());
    }

    public function test_upload_and_confirm_vital_photo_ocr(): void
    {
        Storage::fake('public');
        [$elderlyUser, $elderlyProfile] = $this->createLinkedPair();

        $fakeImage = UploadedFile::fake()->image('bp_monitor.jpg', 600, 400);

        // 1. Senior uploads photo of monitor
        $uploadRes = $this->actingAs($elderlyUser)
            ->postJson(route('capture.upload'), [
                'image' => $fakeImage,
                'session_type' => 'vital_photo_ocr',
            ]);

        $uploadRes->assertOk()
            ->assertJson(['success' => true]);

        $sessionId = $uploadRes->json('session.id');
        $session = CaptureSession::findOrFail($sessionId);

        // 2. Senior confirms reading
        $confirmRes = $this->actingAs($elderlyUser)
            ->postJson(route('capture.confirm', $session), [
                'confirmed_data' => [
                    'type' => 'blood_pressure',
                    'value_text' => '124/82',
                    'unit' => 'mmHg',
                ],
            ]);

        $confirmRes->assertOk()
            ->assertJson([
                'success' => true,
                'type' => 'vital',
            ]);

        $this->assertDatabaseHas('health_metrics', [
            'elderly_id' => $elderlyProfile->id,
            'type' => 'blood_pressure',
            'value_text' => '124/82',
            'source' => 'camera_ocr',
        ]);
    }
}
