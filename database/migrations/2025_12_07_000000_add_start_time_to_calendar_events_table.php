<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add `start_time` only if it's not present already.
        if (!Schema::hasColumn('calendar_events', 'start_time')) {
            Schema::table('calendar_events', function (Blueprint $table) {
                // Use datetime to capture full timestamp; nullable to avoid breaking existing rows
                $table->dateTime('start_time')->nullable()->after('event_date');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('calendar_events', 'start_time')) {
            Schema::table('calendar_events', function (Blueprint $table) {
                $table->dropColumn('start_time');
            });
        }
    }
};
