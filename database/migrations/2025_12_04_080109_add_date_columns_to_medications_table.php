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
        // Add columns only if they don't already exist (safe for environments
        // where another migration or manual change already added these fields).
        if (!Schema::hasColumn('medications', 'start_date') ||
            !Schema::hasColumn('medications', 'end_date') ||
            !Schema::hasColumn('medications', 'specific_dates')) {
            Schema::table('medications', function (Blueprint $table) {
                if (!Schema::hasColumn('medications', 'start_date')) {
                    $table->date('start_date')->nullable()->after('times_of_day');
                }
                if (!Schema::hasColumn('medications', 'end_date')) {
                    $table->date('end_date')->nullable()->after('start_date');
                }
                if (!Schema::hasColumn('medications', 'specific_dates')) {
                    $table->json('specific_dates')->nullable()->after('days_of_week');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop columns only if they exist to avoid errors when rolling back
        // in environments where the columns were added by a different migration.
        Schema::table('medications', function (Blueprint $table) {
            if (Schema::hasColumn('medications', 'start_date') ||
                Schema::hasColumn('medications', 'end_date') ||
                Schema::hasColumn('medications', 'specific_dates')) {
                $cols = [];
                if (Schema::hasColumn('medications', 'start_date')) $cols[] = 'start_date';
                if (Schema::hasColumn('medications', 'end_date')) $cols[] = 'end_date';
                if (Schema::hasColumn('medications', 'specific_dates')) $cols[] = 'specific_dates';

                if (!empty($cols)) {
                    $table->dropColumn($cols);
                }
            }
        });
    }
};
