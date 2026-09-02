<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * M5 — capture sessions were specified as short-lived and shipped permanent.
 *
 * A capture session holds an uploaded photograph of a prescription label or a
 * home vitals monitor. That is PHI, stored under image_path with no expiry and
 * no cleanup job, so every scan a patient has ever taken is retained forever.
 *
 * This adds the expiry the plan called for and backfills existing rows, so the
 * purge command has something to work from.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('capture_sessions', function (Blueprint $table) {
            $table->timestamp('expires_at')->nullable()->index()->after('confirmed_at');
        });

        // Existing sessions get the standard retention window measured from
        // when they were created, so nothing is deleted unexpectedly early.
        $hours = (int) config('capture.retention_hours', 24);

        DB::table('capture_sessions')
            ->whereNull('expires_at')
            ->update(['expires_at' => DB::raw("created_at + interval '{$hours} hours'")]);
    }

    public function down(): void
    {
        Schema::table('capture_sessions', function (Blueprint $table) {
            $table->dropColumn('expires_at');
        });
    }
};
