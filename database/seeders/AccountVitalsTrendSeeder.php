<?php

namespace Database\Seeders;

use App\Models\HealthMetric;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AccountVitalsTrendSeeder extends Seeder
{
    private const DEFAULT_EMAIL = 'santiagomarcstephen@gmail.com';
    private const DAYS_TO_SEED = 35;

    /**
     * Seed 5 weeks of vital records for one account so AI trend analysis has enough history.
     */
    public function run(): void
    {
        $targetUserId = env('TREND_SEED_USER_ID');
        $targetEmail = env('TREND_SEED_EMAIL', self::DEFAULT_EMAIL);

        $userQuery = User::query()->with('profile');
        if (!empty($targetUserId)) {
            $userQuery->whereKey((int) $targetUserId);
        } else {
            $userQuery->where('email', $targetEmail);
        }

        $user = $userQuery->first();
        if (!$user) {
            $this->command?->error('Target user not found. Set TREND_SEED_EMAIL or TREND_SEED_USER_ID.');

            return;
        }

        $profile = $user->profile;
        if (!$profile) {
            $this->command?->error("User {$user->email} has no profile. Create profile first.");

            return;
        }

        $elderlyId = $profile->id;
        $startDate = Carbon::today()->subDays(self::DAYS_TO_SEED - 1);

        $inserted = DB::transaction(function () use ($elderlyId, $startDate): int {
            // Keep seeding idempotent by replacing only prior trend-seed records.
            HealthMetric::query()
                ->where('elderly_id', $elderlyId)
                ->where('notes', 'like', '[trend-seed]%')
                ->delete();

            $count = 0;
            for ($offset = 0; $offset < self::DAYS_TO_SEED; $offset++) {
                $day = $startDate->copy()->addDays($offset);

                $heartRateBase = 70 + intdiv($offset, 10);
                $sugarBase = 102 + intdiv($offset * 3, 5);
                $tempBase = 36.6 + ($offset > 27 ? 0.2 : 0.0);
                $systolicBase = 116 + intdiv($offset * 2, 5);
                $diastolicBase = 74 + intdiv($offset, 4);

                $readings = [
                    [
                        'type' => 'heart_rate',
                        'value' => $heartRateBase + random_int(-4, 4),
                        'value_text' => null,
                        'unit' => 'bpm',
                        'measured_at' => $day->copy()->setTime(8, random_int(0, 30)),
                    ],
                    [
                        'type' => 'heart_rate',
                        'value' => $heartRateBase + random_int(-2, 6),
                        'value_text' => null,
                        'unit' => 'bpm',
                        'measured_at' => $day->copy()->setTime(19, random_int(0, 30)),
                    ],
                    [
                        'type' => 'sugar_level',
                        'value' => $sugarBase + random_int(-8, 9),
                        'value_text' => null,
                        'unit' => 'mg/dL',
                        'measured_at' => $day->copy()->setTime(7, random_int(0, 25)),
                    ],
                    [
                        'type' => 'sugar_level',
                        'value' => $sugarBase + random_int(0, 15),
                        'value_text' => null,
                        'unit' => 'mg/dL',
                        'measured_at' => $day->copy()->setTime(20, random_int(0, 25)),
                    ],
                    [
                        'type' => 'temperature',
                        'value' => round($tempBase + (random_int(-2, 3) / 10), 1),
                        'value_text' => null,
                        'unit' => 'C',
                        'measured_at' => $day->copy()->setTime(9, random_int(0, 20)),
                    ],
                ];

                $systolicMorning = $systolicBase + random_int(-5, 6);
                $diastolicMorning = $diastolicBase + random_int(-4, 4);
                $readings[] = [
                    'type' => 'blood_pressure',
                    'value' => $systolicMorning,
                    'value_text' => $systolicMorning . '/' . $diastolicMorning,
                    'unit' => 'mmHg',
                    'measured_at' => $day->copy()->setTime(8, random_int(35, 59)),
                ];

                $systolicEvening = $systolicBase + random_int(-2, 8);
                $diastolicEvening = $diastolicBase + random_int(-2, 6);
                $readings[] = [
                    'type' => 'blood_pressure',
                    'value' => $systolicEvening,
                    'value_text' => $systolicEvening . '/' . $diastolicEvening,
                    'unit' => 'mmHg',
                    'measured_at' => $day->copy()->setTime(19, random_int(35, 59)),
                ];

                foreach ($readings as $reading) {
                    HealthMetric::query()->create([
                        'elderly_id' => $elderlyId,
                        'type' => $reading['type'],
                        'value' => $reading['value'],
                        'value_text' => $reading['value_text'],
                        'unit' => $reading['unit'],
                        'measured_at' => $reading['measured_at'],
                        'source' => random_int(1, 10) <= 3 ? 'google_fit' : 'manual',
                        'notes' => '[trend-seed] simulated vital history',
                    ]);

                    $count++;
                }
            }

            return $count;
        });

        $this->command?->info("Seeded {$inserted} vital records for {$user->email} (profile #{$elderlyId}).");
        $this->command?->line('Tip: override target with TREND_SEED_EMAIL or TREND_SEED_USER_ID.');
    }
}