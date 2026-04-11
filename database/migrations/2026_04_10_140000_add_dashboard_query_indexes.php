<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('medication_logs')) {
            Schema::table('medication_logs', function (Blueprint $table) {
                try {
                    $table->index(['medication_id', 'scheduled_time', 'is_taken'], 'med_logs_med_sched_taken_idx');
                } catch (\Throwable $e) {
                    // Ignore if the index already exists in the target environment.
                }
            });
        }

        if (Schema::hasTable('notifications')) {
            Schema::table('notifications', function (Blueprint $table) {
                try {
                    $table->index(['elderly_id', 'is_read', 'type'], 'notifications_elderly_read_type_idx');
                } catch (\Throwable $e) {
                    // Ignore if the index already exists in the target environment.
                }
            });
        }

        if (Schema::hasTable('checklists')) {
            Schema::table('checklists', function (Blueprint $table) {
                try {
                    $table->index(['elderly_id', 'due_date', 'is_completed'], 'checklists_elderly_due_completed_idx');
                } catch (\Throwable $e) {
                    // Ignore if the index already exists in the target environment.
                }

                try {
                    $table->index(['elderly_id', 'is_completed', 'due_date'], 'checklists_elderly_completed_due_idx');
                } catch (\Throwable $e) {
                    // Ignore if the index already exists in the target environment.
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('medication_logs')) {
            Schema::table('medication_logs', function (Blueprint $table) {
                try {
                    $table->dropIndex('med_logs_med_sched_taken_idx');
                } catch (\Throwable $e) {
                    // Ignore if index does not exist.
                }
            });
        }

        if (Schema::hasTable('notifications')) {
            Schema::table('notifications', function (Blueprint $table) {
                try {
                    $table->dropIndex('notifications_elderly_read_type_idx');
                } catch (\Throwable $e) {
                    // Ignore if index does not exist.
                }
            });
        }

        if (Schema::hasTable('checklists')) {
            Schema::table('checklists', function (Blueprint $table) {
                try {
                    $table->dropIndex('checklists_elderly_due_completed_idx');
                } catch (\Throwable $e) {
                    // Ignore if index does not exist.
                }

                try {
                    $table->dropIndex('checklists_elderly_completed_due_idx');
                } catch (\Throwable $e) {
                    // Ignore if index does not exist.
                }
            });
        }
    }
};
