<?php

namespace Tests\Unit;

use App\Models\GoogleFitToken;
use App\Models\User;
use App\Models\UserProfile;
use App\Services\GoogleFitService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class GoogleFitServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_build_authorization_url_uses_provided_state(): void
    {
        config()->set('services.google.client_id', 'google-client-id-test');

        $url = app(GoogleFitService::class)->buildAuthorizationUrl(
            'https://example.test/google/callback',
            'state-token-123'
        );

        $this->assertStringContainsString('https://accounts.google.com/o/oauth2/v2/auth?', $url);
        $this->assertStringContainsString('client_id=google-client-id-test', $url);
        $this->assertStringContainsString('redirect_uri=https%3A%2F%2Fexample.test%2Fgoogle%2Fcallback', $url);
        $this->assertStringContainsString('state=state-token-123', $url);
    }

    public function test_get_status_reports_connected_and_expiration_flags(): void
    {
        [$user] = $this->createElderlyUserWithProfile();
        $service = app(GoogleFitService::class);

        $disconnected = $service->getStatus($user->id);
        $this->assertFalse($disconnected['connected']);
        $this->assertTrue($disconnected['is_expired']);
        $this->assertNull($disconnected['expires_at']);

        GoogleFitToken::create([
            'user_id' => $user->id,
            'access_token' => 'access-token',
            'refresh_token' => 'refresh-token',
            'expires_at' => now()->addHour(),
            'scopes' => GoogleFitService::SCOPES,
        ]);

        $connected = $service->getStatus($user->id);
        $this->assertTrue($connected['connected']);
        $this->assertFalse($connected['is_expired']);
        $this->assertNotNull($connected['expires_at']);
    }

    public function test_sync_all_throws_when_google_fit_is_not_connected(): void
    {
        [$user, $profile] = $this->createElderlyUserWithProfile();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Google Fit not connected. Please connect first.');

        app(GoogleFitService::class)->syncAll($profile->id, $user->id);
    }

    public function test_sync_all_persists_heart_rate_blood_pressure_temperature_and_steps(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-04-11 10:00:00'));
        try {
            [$user, $profile] = $this->createElderlyUserWithProfile();

            GoogleFitToken::create([
                'user_id' => $user->id,
                'access_token' => 'access-token',
                'refresh_token' => 'refresh-token',
                'expires_at' => now()->addHour(),
                'scopes' => GoogleFitService::SCOPES,
            ]);

            $heartSourceId = 'derived:com.google.heart_rate.bpm:phone';
            $bpSourceId = 'raw:com.google.blood_pressure:phone';
            $tempSourceId = 'raw:com.google.body.temperature:phone';
            $heartNanos = (now()->subHour()->timestamp * 1000000000);
            $bpNanos = (now()->subHours(2)->timestamp * 1000000000);
            $tempNanos = (now()->subHours(3)->timestamp * 1000000000);

            Http::fake(function ($request) use ($heartSourceId, $bpSourceId, $tempSourceId, $heartNanos, $bpNanos, $tempNanos) {
                $url = $request->url();

                if (str_contains($url, '/dataSources') && !str_contains($url, '/datasets/')) {
                    return Http::response([
                        'dataSource' => [
                            [
                                'dataStreamId' => $heartSourceId,
                                'dataType' => ['name' => 'com.google.heart_rate.bpm'],
                            ],
                            [
                                'dataStreamId' => $bpSourceId,
                                'dataType' => ['name' => 'com.google.blood_pressure'],
                            ],
                            [
                                'dataStreamId' => $tempSourceId,
                                'dataType' => ['name' => 'com.google.body.temperature'],
                            ],
                        ],
                    ], 200);
                }

                if (str_contains($url, "/dataSources/{$heartSourceId}/datasets/")) {
                    return Http::response([
                        'point' => [
                            [
                                'startTimeNanos' => (string) $heartNanos,
                                'value' => [['fpVal' => 72.0]],
                            ],
                        ],
                    ], 200);
                }

                if (str_contains($url, "/dataSources/{$bpSourceId}/datasets/")) {
                    return Http::response([
                        'point' => [
                            [
                                'startTimeNanos' => (string) $bpNanos,
                                'value' => [
                                    ['fpVal' => 120.0],
                                    ['fpVal' => 80.0],
                                ],
                            ],
                        ],
                    ], 200);
                }

                if (str_contains($url, "/dataSources/{$tempSourceId}/datasets/")) {
                    return Http::response([
                        'point' => [
                            [
                                'startTimeNanos' => (string) $tempNanos,
                                'value' => [['fpVal' => 36.8]],
                            ],
                        ],
                    ], 200);
                }

                if (str_contains($url, '/dataset:aggregate')) {
                    $payload = $request->data();
                    $metric = $payload['aggregateBy'][0]['dataTypeName'] ?? null;

                    if ($metric === 'com.google.step_count.delta') {
                        return Http::response([
                            'bucket' => [
                                [
                                    'dataset' => [
                                        [
                                            'point' => [
                                                ['value' => [['intVal' => 1234]]],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ], 200);
                    }

                    return Http::response(['bucket' => []], 200);
                }

                return Http::response([], 404);
            });

            $synced = app(GoogleFitService::class)->syncAll($profile->id, $user->id);

            $this->assertSame('1 readings', $synced['heart_rate']);
            $this->assertSame('1 readings', $synced['blood_pressure']);
            $this->assertSame('1 readings', $synced['temperature']);
            $this->assertSame(1234, $synced['steps']);

            $this->assertDatabaseHas('health_metrics', [
                'elderly_id' => $profile->id,
                'type' => 'heart_rate',
                'source' => 'google_fit',
                'value' => 72,
                'unit' => 'bpm',
            ]);

            $this->assertDatabaseHas('health_metrics', [
                'elderly_id' => $profile->id,
                'type' => 'blood_pressure',
                'source' => 'google_fit',
                'value' => 120,
                'value_text' => '120/80',
                'unit' => 'mmHg',
            ]);

            $this->assertDatabaseHas('health_metrics', [
                'elderly_id' => $profile->id,
                'type' => 'temperature',
                'source' => 'google_fit',
                'value' => 36.8,
                'unit' => '°C',
            ]);

            $this->assertDatabaseHas('health_metrics', [
                'elderly_id' => $profile->id,
                'type' => 'steps',
                'source' => 'google_fit',
                'value' => 1234,
                'unit' => 'steps',
            ]);
        } finally {
            Carbon::setTestNow();
        }
    }

    /**
     * @return array{0: User, 1: UserProfile}
     */
    private function createElderlyUserWithProfile(): array
    {
        $user = User::factory()->create();
        $profile = UserProfile::create([
            'user_id' => $user->id,
            'user_type' => 'elderly',
            'username' => 'elderly-googlefit-' . $user->id,
            'profile_completed' => true,
            'profile_skipped' => false,
        ]);

        return [$user, $profile];
    }
}
