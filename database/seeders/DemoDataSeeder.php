<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\HealthMetric;
use App\Models\Medication;
use App\Models\MedicationLog;
use App\Models\Checklist;
use Carbon\Carbon;

class DemoDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Find the user by email
        $user = User::where('email', 'santiagomarcstephen@gmail.com')->first();
        
        if (!$user) {
            $this->command->error('User not found! Make sure the account exists first.');
            return;
        }

        $profile = $user->profile;
        
        if (!$profile) {
            $this->command->error('User profile not found!');
            return;
        }

        $elderlyId = $profile->id;
        $caregiverId = $profile->caregiver_id; // May be null

        $this->command->info("Seeding data for: {$user->name} (ID: {$elderlyId})");

        // Seed Health Vitals
        $this->seedHealthVitals($elderlyId);

        // Seed Medications & Logs
        $this->seedMedications($elderlyId, $caregiverId);

        // Seed Checklists
        $this->seedChecklists($elderlyId, $caregiverId);

        $this->command->info('✅ Demo data seeded successfully!');
    }

    /**
     * Seed health vitals with 10 entries per type
     */
    private function seedHealthVitals($elderlyId)
    {
        $this->command->info('Seeding health vitals...');

        $vitalTypes = [
            'heart_rate' => [
                'min' => 60,
                'max' => 100,
                'normal_min' => 60,
                'normal_max' => 100,
                'unit' => 'bpm'
            ],
            'blood_pressure' => [
                'systolic_min' => 90,
                'systolic_max' => 140,
                'diastolic_min' => 60,
                'diastolic_max' => 90,
            ],
            'sugar_level' => [
                'min' => 70,
                'max' => 140,
                'normal_min' => 70,
                'normal_max' => 100,
                'unit' => 'mg/dL'
            ],
            'temperature' => [
                'min' => 36.0,
                'max' => 38.5,
                'normal_min' => 36.1,
                'normal_max' => 37.2,
                'unit' => '°C'
            ],
        ];

        foreach ($vitalTypes as $type => $config) {
            for ($i = 0; $i < 10; $i++) {
                $daysAgo = $i * 2; // Every 2 days
                $measuredAt = Carbon::now()->subDays($daysAgo)->setTime(rand(8, 20), rand(0, 59));

                // Randomly choose source
                $source = rand(0, 10) > 6 ? 'google_fit' : 'manual';

                // Generate value based on type
                $value = null;
                $valueText = null;
                
                if ($type === 'blood_pressure') {
                    // Generate realistic blood pressure readings
                    $systolic = rand($config['systolic_min'], $config['systolic_max']);
                    $diastolic = rand($config['diastolic_min'], $config['diastolic_max']);
                    $valueText = "{$systolic}/{$diastolic}";
                    
                    // Determine status
                    if ($systolic > 130 || $diastolic > 85) {
                        $status = 'elevated';
                    } elseif ($systolic < 100 || $diastolic < 65) {
                        $status = 'low';
                    } else {
                        $status = 'normal';
                    }
                } elseif ($type === 'temperature') {
                    $value = number_format(rand($config['min'] * 10, $config['max'] * 10) / 10, 1);
                    
                    if ($value >= 37.5) {
                        $status = 'fever';
                    } elseif ($value < 36.1) {
                        $status = 'low';
                    } else {
                        $status = 'normal';
                    }
                } else {
                    // heart_rate or sugar_level
                    $value = rand($config['min'], $config['max']);
                    
                    if ($value > $config['normal_max']) {
                        $status = 'high';
                    } elseif ($value < $config['normal_min']) {
                        $status = 'low';
                    } else {
                        $status = 'normal';
                    }
                }

                HealthMetric::create([
                    'elderly_id' => $elderlyId,
                    'type' => $type,
                    'value' => $value,
                    'value_text' => $valueText,
                    'measured_at' => $measuredAt,
                    'source' => $source,
                    'notes' => $status === 'normal' ? null : ucfirst($status) . ' reading',
                ]);
            }
        }

        $this->command->info('✓ Seeded 40 health vital records (10 per type)');
    }

    /**
     * Seed medications with various scenarios
     */
    private function seedMedications($elderlyId, $caregiverId)
    {
        $this->command->info('Seeding medications...');

        $medications = [
            // 1. Morning medication - taken on time
            [
                'name' => 'Aspirin',
                'dosage' => '100',
                'dosage_unit' => 'mg',
                'instructions' => 'Take with food. Helps prevent heart attack and stroke.',
                'days_of_week' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'],
                'times_of_day' => ['08:00'],
                'start_date' => Carbon::now()->subMonth(),
                'track_inventory' => true,
                'current_stock' => 15,
                'low_stock_threshold' => 10,
            ],
            // 2. Twice daily - one taken, one missed
            [
                'name' => 'Metformin',
                'dosage' => '500',
                'dosage_unit' => 'mg',
                'instructions' => 'For diabetes management. Take with meals.',
                'days_of_week' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'],
                'times_of_day' => ['08:00', '20:00'],
                'start_date' => Carbon::now()->subWeeks(2),
                'track_inventory' => true,
                'current_stock' => 8,
                'low_stock_threshold' => 10,
            ],
            // 3. Three times daily - various statuses
            [
                'name' => 'Lisinopril',
                'dosage' => '10',
                'dosage_unit' => 'mg',
                'instructions' => 'Blood pressure medication. Avoid grapefruit juice.',
                'days_of_week' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'],
                'times_of_day' => ['07:00', '14:00', '21:00'],
                'start_date' => Carbon::now()->subMonth(),
                'track_inventory' => false,
            ],
            // 4. Specific days only - not today
            [
                'name' => 'Vitamin D',
                'dosage' => '1000',
                'dosage_unit' => 'IU',
                'instructions' => 'Take in the morning for better absorption.',
                'days_of_week' => ['Monday', 'Wednesday', 'Friday'],
                'times_of_day' => ['09:00'],
                'start_date' => Carbon::now()->subMonth(),
                'track_inventory' => true,
                'current_stock' => 30,
                'low_stock_threshold' => 5,
            ],
            // 5. Evening medication - not yet time
            [
                'name' => 'Melatonin',
                'dosage' => '5',
                'dosage_unit' => 'mg',
                'instructions' => 'Take 30 minutes before bedtime.',
                'days_of_week' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'],
                'times_of_day' => ['22:30'],
                'start_date' => Carbon::now()->subWeek(),
                'track_inventory' => false,
            ],
            // 6. PRN (As needed) - taken late
            [
                'name' => 'Ibuprofen',
                'dosage' => '400',
                'dosage_unit' => 'mg',
                'instructions' => 'Take as needed for pain. Do not exceed 1200mg per day.',
                'days_of_week' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'],
                'times_of_day' => ['12:00'],
                'start_date' => Carbon::now()->subWeek(),
                'track_inventory' => true,
                'current_stock' => 20,
                'low_stock_threshold' => 15,
            ],
        ];

        foreach ($medications as $medData) {
            $medication = Medication::create([
                'elderly_id' => $elderlyId,
                'caregiver_id' => $caregiverId,
                'name' => $medData['name'],
                'dosage' => $medData['dosage'],
                'dosage_unit' => $medData['dosage_unit'],
                'instructions' => $medData['instructions'],
                'days_of_week' => $medData['days_of_week'],
                'times_of_day' => $medData['times_of_day'],
                'start_date' => $medData['start_date'],
                'is_active' => true,
                'track_inventory' => $medData['track_inventory'] ?? false,
                'current_stock' => $medData['current_stock'] ?? 0,
                'low_stock_threshold' => $medData['low_stock_threshold'] ?? 5,
            ]);

            // Create medication logs for the past 7 days
            $this->seedMedicationLogs($medication, $elderlyId);
        }

        $this->command->info('✓ Seeded ' . count($medications) . ' medications with logs');
    }

    /**
     * Seed medication logs for various scenarios
     */
    private function seedMedicationLogs($medication, $elderlyId)
    {
        $today = Carbon::today();
        $currentDayName = Carbon::now()->format('l');

        // Generate logs for past 7 days
        for ($i = 0; $i < 7; $i++) {
            $date = $today->copy()->subDays($i);
            $dayName = $date->format('l');

            // Check if medication is scheduled for this day
            if (!in_array($dayName, $medication->days_of_week)) {
                continue;
            }

            // Create logs for each time of day
            foreach ($medication->times_of_day as $time) {
                $scheduledTime = Carbon::parse($date->format('Y-m-d') . ' ' . $time);

                // Skip future times
                if ($scheduledTime->isFuture()) {
                    continue;
                }

                // Determine log status based on medication and time
                $isTaken = false;
                $takenAt = null;

                if ($i === 0) {
                    // Today's doses
                    $now = Carbon::now();
                    $hoursSinceScheduled = $now->diffInHours($scheduledTime, false);

                    if ($hoursSinceScheduled > 1) {
                        // Past grace period
                        if ($medication->name === 'Metformin' && $time === '08:00') {
                            // Missed dose
                            $isTaken = false;
                        } elseif ($medication->name === 'Ibuprofen') {
                            // Taken late
                            $isTaken = true;
                            $takenAt = $scheduledTime->copy()->addMinutes(90);
                        } elseif ($medication->name === 'Aspirin' || $medication->name === 'Lisinopril') {
                            // Taken on time
                            $isTaken = true;
                            $takenAt = $scheduledTime->copy()->addMinutes(rand(0, 15));
                        }
                    }
                } else {
                    // Past days - randomize with 80% taken rate
                    $isTaken = rand(1, 10) <= 8;
                    if ($isTaken) {
                        // Some on time, some late
                        $minutesDelay = rand(0, 10) > 7 ? rand(60, 120) : rand(0, 15);
                        $takenAt = $scheduledTime->copy()->addMinutes($minutesDelay);
                    }
                }

                MedicationLog::create([
                    'elderly_id' => $elderlyId,
                    'medication_id' => $medication->id,
                    'scheduled_time' => $scheduledTime,
                    'is_taken' => $isTaken,
                    'taken_at' => $takenAt,
                ]);
            }
        }
    }

    /**
     * Seed checklists with various scenarios
     */
    private function seedChecklists($elderlyId, $caregiverId)
    {
        $this->command->info('Seeding checklists...');

        $today = Carbon::today();
        $tasks = [
            // Today's tasks - various statuses
            [
                'task' => 'Morning Walk',
                'description' => 'Take a 15-minute walk around the neighborhood',
                'category' => 'Exercise',
                'due_date' => $today,
                'due_time' => '07:00',
                'priority' => 'high',
                'notes' => 'Good for heart health',
                'is_completed' => true,
                'completed_at' => $today->copy()->setTime(7, 15),
            ],
            [
                'task' => 'Drink 8 glasses of water',
                'description' => 'Stay hydrated throughout the day',
                'category' => 'Health',
                'due_date' => $today,
                'due_time' => '20:00',
                'priority' => 'medium',
                'notes' => 'Track with water bottle',
                'is_completed' => false,
            ],
            [
                'task' => 'Take Blood Pressure Reading',
                'description' => 'Record morning blood pressure',
                'category' => 'Health',
                'due_date' => $today,
                'due_time' => '09:00',
                'priority' => 'high',
                'notes' => 'Use home monitor',
                'is_completed' => true,
                'completed_at' => $today->copy()->setTime(9, 30),
            ],
            [
                'task' => 'Call Dr. Smith',
                'description' => 'Schedule follow-up appointment',
                'category' => 'Medical',
                'due_date' => $today,
                'due_time' => '14:00',
                'priority' => 'high',
                'notes' => 'Bring insurance card',
                'is_completed' => false,
            ],
            [
                'task' => 'Prepare meals for tomorrow',
                'description' => 'Meal prep to save time',
                'category' => 'Daily',
                'due_date' => $today,
                'due_time' => '18:00',
                'priority' => 'medium',
                'is_completed' => false,
            ],
            [
                'task' => 'Light Stretching',
                'description' => 'Do 10 minutes of gentle stretching',
                'category' => 'Exercise',
                'due_date' => $today,
                'due_time' => '16:00',
                'priority' => 'low',
                'notes' => 'Focus on lower back',
                'is_completed' => false,
            ],

            // Tomorrow's tasks
            [
                'task' => 'Pharmacy Pickup',
                'description' => 'Pick up prescription refills',
                'category' => 'Medical',
                'due_date' => $today->copy()->addDay(),
                'due_time' => '10:00',
                'priority' => 'high',
                'notes' => 'Bring prescription list',
                'is_completed' => false,
            ],
            [
                'task' => 'Attend Yoga Class',
                'description' => 'Senior yoga at community center',
                'category' => 'Exercise',
                'due_date' => $today->copy()->addDay(),
                'due_time' => '11:00',
                'priority' => 'medium',
                'notes' => 'Bring yoga mat',
                'is_completed' => false,
            ],

            // Yesterday's tasks (some completed, some missed)
            [
                'task' => 'Water Plants',
                'description' => 'Water indoor and outdoor plants',
                'category' => 'Daily',
                'due_date' => $today->copy()->subDay(),
                'due_time' => '08:00',
                'priority' => 'low',
                'is_completed' => true,
                'completed_at' => $today->copy()->subDay()->setTime(8, 30),
            ],
            [
                'task' => 'Check Blood Sugar',
                'description' => 'Morning fasting blood sugar check',
                'category' => 'Health',
                'due_date' => $today->copy()->subDay(),
                'due_time' => '07:00',
                'priority' => 'high',
                'is_completed' => true,
                'completed_at' => $today->copy()->subDay()->setTime(7, 10),
            ],
            [
                'task' => 'Video Call with Family',
                'description' => 'Weekly family check-in',
                'category' => 'Social',
                'due_date' => $today->copy()->subDay(),
                'due_time' => '19:00',
                'priority' => 'medium',
                'notes' => 'Use tablet in living room',
                'is_completed' => false, // Missed
            ],

            // Next week tasks
            [
                'task' => 'Dentist Appointment',
                'description' => 'Regular dental checkup',
                'category' => 'Medical',
                'due_date' => $today->copy()->addDays(3),
                'due_time' => '14:30',
                'priority' => 'high',
                'notes' => 'Bring insurance card',
                'is_completed' => false,
            ],
            [
                'task' => 'Grocery Shopping',
                'description' => 'Weekly grocery run',
                'category' => 'Daily',
                'due_date' => $today->copy()->addDays(2),
                'due_time' => '10:00',
                'priority' => 'medium',
                'notes' => 'Check pantry list',
                'is_completed' => false,
            ],
        ];

        foreach ($tasks as $taskData) {
            Checklist::create([
                'elderly_id' => $elderlyId,
                'caregiver_id' => $caregiverId,
                'task' => $taskData['task'],
                'description' => $taskData['description'],
                'category' => $taskData['category'],
                'due_date' => $taskData['due_date'],
                'due_time' => $taskData['due_time'],
                'priority' => $taskData['priority'] ?? 'medium',
                'notes' => $taskData['notes'] ?? null,
                'is_completed' => $taskData['is_completed'],
                'completed_at' => $taskData['completed_at'] ?? null,
            ]);
        }

        $this->command->info('✓ Seeded ' . count($tasks) . ' checklist tasks');
    }
}
