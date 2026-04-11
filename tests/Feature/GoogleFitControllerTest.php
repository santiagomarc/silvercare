<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserProfile;
use App\Services\GoogleFitService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class GoogleFitControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_connect_redirects_to_authorization_url_from_service(): void
    {
        [$user] = $this->createElderlyUserWithProfile();

        $service = $this->mock(GoogleFitService::class, function ($mock): void {
            $mock->shouldReceive('buildAuthorizationUrl')
                ->once()
                ->with(
                    route('elderly.googlefit.callback'),
                    \Mockery::on(fn ($state) => is_string($state) && strlen($state) === 40)
                )
                ->andReturn('https://accounts.google.com/o/oauth2/v2/auth?mock=1');
        });

        $response = $this->actingAs($user)
            ->get(route('elderly.googlefit.connect'));

        $response->assertRedirect('https://accounts.google.com/o/oauth2/v2/auth?mock=1');
        $response->assertSessionHas('google_fit_oauth_state');
    }

    public function test_callback_redirects_with_error_when_code_missing(): void
    {
        [$user] = $this->createElderlyUserWithProfile();

        $response = $this->actingAs($user)
            ->withSession(['google_fit_oauth_state' => 'state-123'])
            ->get(route('elderly.googlefit.callback', ['state' => 'state-123']));

        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('error', 'No authorization code received');
    }

    public function test_callback_rejects_invalid_oauth_state(): void
    {
        [$user] = $this->createElderlyUserWithProfile();

        $service = $this->mock(GoogleFitService::class, function ($mock): void {
            $mock->shouldReceive('exchangeCodeForTokens')->never();
            $mock->shouldReceive('storeTokens')->never();
        });

        $response = $this->actingAs($user)
            ->withSession(['google_fit_oauth_state' => 'expected-state'])
            ->get(route('elderly.googlefit.callback', [
                'code' => 'oauth-code',
                'state' => 'wrong-state',
            ]));

        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('error', 'Invalid Google Fit authorization state. Please try again.');
    }

    public function test_callback_stores_tokens_and_redirects_on_success(): void
    {
        [$user] = $this->createElderlyUserWithProfile();

        $service = $this->mock(GoogleFitService::class, function ($mock) use ($user): void {
            $mock->shouldReceive('exchangeCodeForTokens')
                ->once()
                ->with('oauth-code', route('elderly.googlefit.callback'))
                ->andReturn([
                    'access_token' => 'access-token',
                    'refresh_token' => 'refresh-token',
                    'expires_in' => 3600,
                    'scopes' => GoogleFitService::SCOPES,
                ]);

            $mock->shouldReceive('storeTokens')
                ->once()
                ->with($user->id, \Mockery::on(fn ($data) => ($data['access_token'] ?? null) === 'access-token'));
        });

        $response = $this->actingAs($user)
            ->withSession(['google_fit_oauth_state' => 'state-123'])
            ->get(route('elderly.googlefit.callback', [
                'code' => 'oauth-code',
                'state' => 'state-123',
            ]));

        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('success');
    }

    public function test_callback_redirects_with_error_when_exchange_fails(): void
    {
        [$user] = $this->createElderlyUserWithProfile();

        $service = $this->mock(GoogleFitService::class, function ($mock): void {
            $mock->shouldReceive('exchangeCodeForTokens')
                ->once()
                ->andReturnNull();

            $mock->shouldReceive('storeTokens')->never();
        });

        $response = $this->actingAs($user)
            ->withSession(['google_fit_oauth_state' => 'state-123'])
            ->get(route('elderly.googlefit.callback', [
                'code' => 'oauth-code',
                'state' => 'state-123',
            ]));

        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('error', 'Failed to get access token from Google');
    }

    public function test_status_returns_connection_payload_from_service(): void
    {
        [$user] = $this->createElderlyUserWithProfile();

        $service = $this->mock(GoogleFitService::class, function ($mock) use ($user): void {
            $mock->shouldReceive('getStatus')
                ->once()
                ->with($user->id)
                ->andReturn([
                    'connected' => true,
                    'expires_at' => '2026-04-11T12:00:00+00:00',
                    'is_expired' => false,
                ]);
        });

        $response = $this->actingAs($user)
            ->getJson(route('elderly.googlefit.status'));

        $response->assertOk()
            ->assertJson([
                'connected' => true,
                'expires_at' => '2026-04-11T12:00:00+00:00',
                'is_expired' => false,
            ]);
    }

    public function test_sync_returns_success_payload_from_service(): void
    {
        [$user, $profile] = $this->createElderlyUserWithProfile();

        $service = $this->mock(GoogleFitService::class, function ($mock) use ($profile, $user): void {
            $mock->shouldReceive('syncAll')
                ->once()
                ->with($profile->id, $user->id)
                ->andReturn([
                    'heart_rate' => '2 readings',
                    'steps' => 2500,
                ]);
        });

        $response = $this->actingAs($user)
            ->postJson(route('elderly.googlefit.sync'));

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Google Fit data synced successfully!',
                'synced' => [
                    'heart_rate' => '2 readings',
                    'steps' => 2500,
                ],
            ]);
    }

    public function test_sync_maps_runtime_exception_status_codes(): void
    {
        [$user, $profile] = $this->createElderlyUserWithProfile();

        $service = $this->mock(GoogleFitService::class, function ($mock): void {
            $mock->shouldReceive('syncAll')
                ->once()
                ->andThrow(new RuntimeException('Google Fit not connected. Please connect first.', 400));
        });

        $response = $this->actingAs($user)
            ->postJson(route('elderly.googlefit.sync'));

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Google Fit not connected. Please connect first.',
            ]);
    }

    public function test_disconnect_json_returns_success_and_calls_service(): void
    {
        [$user] = $this->createElderlyUserWithProfile();

        $service = $this->mock(GoogleFitService::class, function ($mock) use ($user): void {
            $mock->shouldReceive('disconnectUser')
                ->once()
                ->with($user->id);
        });

        $response = $this->actingAs($user)
            ->postJson(route('elderly.googlefit.disconnect'));

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Google Fit disconnected successfully',
            ]);
    }

    public function test_disconnect_non_json_redirects_with_success_message(): void
    {
        [$user] = $this->createElderlyUserWithProfile();

        $service = $this->mock(GoogleFitService::class, function ($mock) use ($user): void {
            $mock->shouldReceive('disconnectUser')
                ->once()
                ->with($user->id);
        });

        $response = $this->actingAs($user)
            ->post(route('elderly.googlefit.disconnect'));

        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('success', 'Google Fit disconnected');
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
            'username' => 'elderly-googlefit-feature-' . $user->id,
            'profile_completed' => true,
            'profile_skipped' => false,
            'is_active' => true,
        ]);

        return [$user, $profile];
    }
}
