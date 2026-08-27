<?php

namespace Tests\Feature;

use App\Models\CareCheckin;
use App\Models\HealthMetric;
use App\Models\Medication;
use App\Models\User;
use App\Models\UserProfile;
use App\Services\VoiceVitalParserService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VoiceVitalCaptureTest extends TestCase
{
    use RefreshDatabase;

    private function createElderlyUser(): array
    {
        $user = User::factory()->create(['name' => 'Alice Senior']);
        $profile = UserProfile::create([
            'user_id' => $user->id,
            'user_type' => 'elderly',
            'username' => 'alicesenior',
            'profile_completed' => true,
        ]);

        return [$user, $profile];
    }

    public function test_parser_service_extracts_blood_pressure(): void
    {
        [$user, $profile] = $this->createElderlyUser();
        $parser = app(VoiceVitalParserService::class);

        $result = $parser->parse('My blood pressure is 128 over 84', $profile);

        $this->assertEquals('vital_reading', $result['intent']);
        $this->assertEquals('blood_pressure', $result['type']);
        $this->assertEquals('128/84', $result['value_text']);
        $this->assertEquals(128, $result['systolic']);
        $this->assertEquals(84, $result['diastolic']);
    }

    public function test_parser_service_extracts_blood_sugar(): void
    {
        [$user, $profile] = $this->createElderlyUser();
        $parser = app(VoiceVitalParserService::class);

        $result = $parser->parse('Blood sugar is 115', $profile);

        $this->assertEquals('vital_reading', $result['intent']);
        $this->assertEquals('sugar_level', $result['type']);
        $this->assertEquals(115.0, $result['value']);
    }

    public function test_parser_service_extracts_checkin(): void
    {
        [$user, $profile] = $this->createElderlyUser();
        $parser = app(VoiceVitalParserService::class);

        $result = $parser->parse("I'm feeling ok today", $profile);

        $this->assertEquals('checkin', $result['intent']);
        $this->assertEquals('ok', $result['status']);
    }

    public function test_voice_parse_and_confirm_endpoints(): void
    {
        [$user, $profile] = $this->createElderlyUser();

        // 1. Parse via API
        $parseRes = $this->actingAs($user)
            ->postJson(route('voice.parse'), [
                'transcript' => 'My temperature is 36.8',
            ]);

        $parseRes->assertOk()
            ->assertJson([
                'success' => true,
                'parsed' => [
                    'intent' => 'vital_reading',
                    'type' => 'temperature',
                    'value' => 36.8,
                ],
            ]);

        $parsedPayload = $parseRes->json('parsed');

        // 2. Confirm via API
        $confirmRes = $this->actingAs($user)
            ->postJson(route('voice.confirm'), [
                'intent' => 'vital_reading',
                'payload' => $parsedPayload,
            ]);

        $confirmRes->assertOk()
            ->assertJson([
                'success' => true,
                'type' => 'vital',
            ]);

        $this->assertDatabaseHas('health_metrics', [
            'elderly_id' => $profile->id,
            'type' => 'temperature',
            'value' => 36.8,
            'source' => 'voice_capture',
        ]);
    }
}
