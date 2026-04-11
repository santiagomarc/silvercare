<?php

namespace Tests\Unit;

use App\Models\HealthMetric;
use App\Models\User;
use App\Models\UserProfile;
use App\Services\HealthAnalyticsService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HealthAnalyticsServiceExtractionTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_steps_analytics_returns_today_weekly_total_average_and_history(): void
    {
        $elderlyProfile = $this->createElderlyProfile();

        HealthMetric::create([
            'elderly_id' => $elderlyProfile->id,
            'type' => 'steps',
            'value' => 1200,
            'unit' => 'steps',
            'measured_at' => Carbon::now()->subDays(2),
            'source' => 'manual',
        ]);

        HealthMetric::create([
            'elderly_id' => $elderlyProfile->id,
            'type' => 'steps',
            'value' => 3000,
            'unit' => 'steps',
            'measured_at' => Carbon::now()->subDays(1),
            'source' => 'google_fit',
        ]);

        HealthMetric::create([
            'elderly_id' => $elderlyProfile->id,
            'type' => 'steps',
            'value' => 2500,
            'unit' => 'steps',
            'measured_at' => Carbon::now(),
            'source' => 'google_fit',
        ]);

        $steps = app(HealthAnalyticsService::class)->getStepsAnalytics($elderlyProfile->id);

        $this->assertNotNull($steps['today']);
        $this->assertSame(2500, $steps['today']['value']);
        $this->assertSame(6000, $steps['today']['goal']);
        $this->assertSame(6700, $steps['weeklyTotal']);
        $this->assertSame(2233, $steps['weeklyAvg']);
        $this->assertCount(3, $steps['weeklyHistory']);
    }

    public function test_calculate_bmi_returns_expected_category_and_color(): void
    {
        $service = app(HealthAnalyticsService::class);

        $normal = $service->calculateBmi(65.0, 170.0);
        $this->assertSame(22.5, $normal['bmi']);
        $this->assertSame('Normal', $normal['category']);
        $this->assertSame('green', $normal['color']);

        $missing = $service->calculateBmi(null, 170.0);
        $this->assertNull($missing['bmi']);
        $this->assertSame('gray', $missing['color']);
    }

    private function createElderlyProfile(): UserProfile
    {
        $user = User::factory()->create();

        return UserProfile::create([
            'user_id' => $user->id,
            'user_type' => 'elderly',
            'username' => 'elderly-analytics-' . $user->id,
            'profile_completed' => true,
            'profile_skipped' => false,
        ]);
    }
}
