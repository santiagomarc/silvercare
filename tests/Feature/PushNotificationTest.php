<?php

namespace Tests\Feature;

use App\Models\Alert;
use App\Models\PushSubscription;
use App\Models\User;
use App\Models\UserProfile;
use App\Services\AlertDeliveryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * H8 — browser push.
 *
 * With C1 fixed and C3 still pending, a caregiver who is not looking at the
 * dashboard has only email. Push is the channel that reaches a locked phone.
 *
 * These tests cover subscription management and the delivery decision. They
 * deliberately do not hit a real push service: what matters here is that the
 * right subscriptions are stored, scoped and selected, and that a missing VAPID
 * configuration degrades quietly instead of breaking the other channels.
 */
class PushNotificationTest extends TestCase
{
    use RefreshDatabase;

    private const ENDPOINT = 'https://fcm.googleapis.com/fcm/send/abc123-example-endpoint';

    /** @return array{0: User, 1: UserProfile, 2: User, 3: UserProfile} */
    private function createLinkedPair(): array
    {
        $caregiverUser = User::factory()->create(['name' => 'Dana Caregiver']);
        $caregiver = UserProfile::create([
            'user_id' => $caregiverUser->id,
            'user_type' => 'caregiver',
            'username' => 'danapush',
            'profile_completed' => true,
        ]);

        $elderlyUser = User::factory()->create(['name' => 'Rosa Senior']);
        $elderly = UserProfile::create([
            'user_id' => $elderlyUser->id,
            'user_type' => 'elderly',
            'username' => 'rosapush',
            'caregiver_id' => $caregiver->id,
            'profile_completed' => true,
        ]);

        return [$caregiverUser, $caregiver, $elderlyUser, $elderly];
    }

    private function subscriptionPayload(string $endpoint = self::ENDPOINT): array
    {
        return [
            'endpoint' => $endpoint,
            'keys' => [
                'p256dh' => 'BNcRdreALRFXTkOOUHK1EtK2wtaz5Ry4YfYCA_0QTpQtUbVlUls0VJXg7A8u-Ts1XbjhazAkj7I99e8QcYP7DkM',
                'auth' => 'tBHItJI5svbpez7KI4CCXg',
            ],
        ];
    }

    public function test_caregiver_can_subscribe_a_device(): void
    {
        [$caregiverUser, $caregiver] = $this->createLinkedPair();

        $this->actingAs($caregiverUser)
            ->postJson(route('push.subscribe'), $this->subscriptionPayload())
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('push_subscriptions', [
            'profile_id' => $caregiver->id,
            'endpoint' => self::ENDPOINT,
        ]);
    }

    public function test_resubscribing_the_same_device_updates_rather_than_duplicates(): void
    {
        [$caregiverUser, $caregiver] = $this->createLinkedPair();

        $this->actingAs($caregiverUser)->postJson(route('push.subscribe'), $this->subscriptionPayload())->assertOk();
        $this->actingAs($caregiverUser)->postJson(route('push.subscribe'), $this->subscriptionPayload())->assertOk();

        $this->assertSame(
            1,
            PushSubscription::where('profile_id', $caregiver->id)->count(),
            'A repeat subscription must not create a second row that would double-notify.'
        );
    }

    public function test_a_caregiver_can_register_more_than_one_device(): void
    {
        [$caregiverUser, $caregiver] = $this->createLinkedPair();

        $this->actingAs($caregiverUser)->postJson(route('push.subscribe'), $this->subscriptionPayload())->assertOk();
        $this->actingAs($caregiverUser)
            ->postJson(route('push.subscribe'), $this->subscriptionPayload('https://fcm.googleapis.com/fcm/send/second-device'))
            ->assertOk();

        $this->assertSame(2, PushSubscription::where('profile_id', $caregiver->id)->count());
    }

    public function test_a_user_cannot_unsubscribe_another_users_device(): void
    {
        [$caregiverUser, $caregiver] = $this->createLinkedPair();
        $this->actingAs($caregiverUser)->postJson(route('push.subscribe'), $this->subscriptionPayload())->assertOk();

        $otherUser = User::factory()->create();
        UserProfile::create([
            'user_id' => $otherUser->id,
            'user_type' => 'caregiver',
            'username' => 'intruder',
            'profile_completed' => true,
        ]);

        $this->actingAs($otherUser)
            ->deleteJson(route('push.unsubscribe'), ['endpoint' => self::ENDPOINT])
            ->assertOk()
            ->assertJson(['message' => 'No matching subscription for this device.']);

        $this->assertDatabaseHas('push_subscriptions', ['profile_id' => $caregiver->id]);
    }

    public function test_owner_can_unsubscribe_their_device(): void
    {
        [$caregiverUser, $caregiver] = $this->createLinkedPair();
        $this->actingAs($caregiverUser)->postJson(route('push.subscribe'), $this->subscriptionPayload())->assertOk();

        $this->actingAs($caregiverUser)
            ->deleteJson(route('push.unsubscribe'), ['endpoint' => self::ENDPOINT])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('push_subscriptions', ['profile_id' => $caregiver->id]);
    }

    public function test_subscribe_requires_authentication(): void
    {
        $this->postJson(route('push.subscribe'), $this->subscriptionPayload())->assertUnauthorized();
    }

    public function test_config_endpoint_reports_whether_push_is_available(): void
    {
        [$caregiverUser] = $this->createLinkedPair();

        config(['webpush.vapid.public_key' => null, 'webpush.vapid.private_key' => null]);
        $this->actingAs($caregiverUser)->getJson(route('push.config'))->assertOk()->assertJson(['enabled' => false]);

        config(['webpush.vapid.public_key' => 'test-public', 'webpush.vapid.private_key' => 'test-private']);
        $this->actingAs($caregiverUser)->getJson(route('push.config'))
            ->assertOk()
            ->assertJson(['enabled' => true, 'public_key' => 'test-public']);
    }

    public function test_missing_vapid_config_does_not_break_the_other_channels(): void
    {
        Mail::fake();
        [, $caregiver, , $elderly] = $this->createLinkedPair();

        config(['webpush.vapid.public_key' => null, 'webpush.vapid.private_key' => null]);

        PushSubscription::create([
            'profile_id' => $caregiver->id,
            'endpoint' => self::ENDPOINT,
            'endpoint_hash' => PushSubscription::hashEndpoint(self::ENDPOINT),
            'p256dh_key' => 'key',
            'auth_token' => 'auth',
        ]);

        $alert = Alert::create([
            'elderly_id' => $elderly->id,
            'severity' => 'critical',
            'source_type' => 'vital_threshold',
            'title' => 'Critical reading',
            'message' => 'A critical reading was recorded.',
            'state' => 'open',
        ]);

        app(AlertDeliveryService::class)->deliver($alert);

        // In-app and email still land…
        $this->assertDatabaseHas('alert_deliveries', [
            'alert_id' => $alert->id,
            'channel' => 'in_app',
            'state' => 'delivered',
        ]);
        $this->assertDatabaseHas('alert_deliveries', [
            'alert_id' => $alert->id,
            'channel' => 'email',
            'state' => 'sent',
        ]);

        // …and push records nothing at all rather than a spurious failure.
        $this->assertDatabaseMissing('alert_deliveries', [
            'alert_id' => $alert->id,
            'channel' => 'browser_push',
        ]);
    }

    public function test_no_push_row_is_written_when_the_caregiver_has_no_devices(): void
    {
        Mail::fake();
        [, , , $elderly] = $this->createLinkedPair();

        config(['webpush.vapid.public_key' => 'pub', 'webpush.vapid.private_key' => 'priv']);

        $alert = Alert::create([
            'elderly_id' => $elderly->id,
            'severity' => 'emergency',
            'source_type' => 'sos',
            'title' => 'SOS',
            'message' => 'Emergency button pressed.',
            'state' => 'open',
        ]);

        app(AlertDeliveryService::class)->deliver($alert);

        $this->assertDatabaseMissing('alert_deliveries', [
            'alert_id' => $alert->id,
            'channel' => 'browser_push',
        ]);
    }

    public function test_warning_alerts_do_not_push(): void
    {
        Mail::fake();
        [, $caregiver, , $elderly] = $this->createLinkedPair();

        config(['webpush.vapid.public_key' => 'pub', 'webpush.vapid.private_key' => 'priv']);

        PushSubscription::create([
            'profile_id' => $caregiver->id,
            'endpoint' => self::ENDPOINT,
            'endpoint_hash' => PushSubscription::hashEndpoint(self::ENDPOINT),
            'p256dh_key' => 'key',
            'auth_token' => 'auth',
        ]);

        $alert = Alert::create([
            'elderly_id' => $elderly->id,
            'severity' => 'warning',
            'source_type' => 'vital_threshold',
            'title' => 'Elevated reading',
            'message' => 'An elevated reading was recorded.',
            'state' => 'open',
        ]);

        app(AlertDeliveryService::class)->deliver($alert);

        // A warning is not worth interrupting someone's day for — it reaches
        // them in-app and on the dashboard instead.
        $this->assertDatabaseMissing('alert_deliveries', [
            'alert_id' => $alert->id,
            'channel' => 'browser_push',
        ]);
    }
}
