<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('medication_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medication_id')->constrained()->onDelete('cascade');
            $table->enum('schedule_type', ['daily', 'weekly', 'specific_date']);
            $table->json('days_of_week')->nullable();
            $table->date('specific_date')->nullable();
            $table->time('time_of_day');
            $table->timestamps();

            $table->index(['medication_id', 'schedule_type']);
            $table->index(['specific_date', 'time_of_day']);
        });

        $now = now();
        $medications = DB::table('medications')
            ->select(['id', 'days_of_week', 'specific_dates', 'times_of_day'])
            ->get();

        foreach ($medications as $medication) {
            $times = $this->normalizeJsonArray($medication->times_of_day);
            if (empty($times)) {
                continue;
            }

            $days = $this->normalizeJsonArray($medication->days_of_week);
            $specificDates = $this->normalizeJsonArray($medication->specific_dates);

            if (!empty($specificDates)) {
                $rows = [];
                foreach ($specificDates as $specificDate) {
                    foreach ($times as $time) {
                        $rows[] = [
                            'medication_id' => $medication->id,
                            'schedule_type' => 'specific_date',
                            'specific_date' => $specificDate,
                            'days_of_week' => null,
                            'time_of_day' => $time,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }
                }

                if (!empty($rows)) {
                    DB::table('medication_schedules')->insert($rows);
                }

                continue;
            }

            $type = !empty($days) ? 'weekly' : 'daily';
            $rows = [];
            foreach ($times as $time) {
                $rows[] = [
                    'medication_id' => $medication->id,
                    'schedule_type' => $type,
                    'days_of_week' => $type === 'weekly' ? json_encode(array_values($days)) : null,
                    'specific_date' => null,
                    'time_of_day' => $time,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            DB::table('medication_schedules')->insert($rows);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medication_schedules');
    }

    /**
     * @return array<int, string>
     */
    private function normalizeJsonArray(mixed $value): array
    {
        if ($value === null) {
            return [];
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (!is_array($decoded)) {
                return [];
            }

            return array_values(array_filter($decoded, fn ($item) => is_string($item) && $item !== ''));
        }

        if (is_array($value)) {
            return array_values(array_filter($value, fn ($item) => is_string($item) && $item !== ''));
        }

        return [];
    }
};
