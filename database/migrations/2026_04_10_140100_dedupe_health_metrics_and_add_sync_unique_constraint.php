<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('health_metrics')) {
            return;
        }

        DB::transaction(function () {
            $duplicateGroups = DB::table('health_metrics')
                ->select(
                    'elderly_id',
                    'type',
                    'source',
                    'measured_at',
                    DB::raw('MAX(id) as keep_id')
                )
                ->groupBy('elderly_id', 'type', 'source', 'measured_at')
                ->havingRaw('COUNT(*) > 1')
                ->get();

            foreach ($duplicateGroups as $group) {
                DB::table('health_metrics')
                    ->where('elderly_id', $group->elderly_id)
                    ->where('type', $group->type)
                    ->where('source', $group->source)
                    ->where('measured_at', $group->measured_at)
                    ->where('id', '!=', $group->keep_id)
                    ->delete();
            }
        });

        Schema::table('health_metrics', function (Blueprint $table) {
            try {
                $table->unique(['elderly_id', 'type', 'source', 'measured_at'], 'health_metrics_sync_unique');
            } catch (\Throwable $e) {
                // Ignore if unique index already exists.
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('health_metrics')) {
            return;
        }

        Schema::table('health_metrics', function (Blueprint $table) {
            try {
                $table->dropUnique('health_metrics_sync_unique');
            } catch (\Throwable $e) {
                // Ignore if unique index does not exist.
            }
        });
    }
};
