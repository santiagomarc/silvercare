<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('checklists')) {
            return;
        }

        Schema::table('checklists', function (Blueprint $table) {
            if (Schema::hasColumn('checklists', 'elderly_id')) {
                try {
                    $table->dropForeign(['elderly_id']);
                } catch (\Throwable $e) {
                    // Foreign key may not exist in some environments.
                }

                $table->foreign('elderly_id')
                    ->references('id')
                    ->on('user_profiles')
                    ->onDelete('cascade');
            }

            if (Schema::hasColumn('checklists', 'caregiver_id')) {
                try {
                    $table->dropForeign(['caregiver_id']);
                } catch (\Throwable $e) {
                    // Foreign key may not exist in some environments.
                }

                $table->foreign('caregiver_id')
                    ->references('id')
                    ->on('user_profiles')
                    ->onDelete('cascade');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('checklists')) {
            return;
        }

        Schema::table('checklists', function (Blueprint $table) {
            if (Schema::hasColumn('checklists', 'elderly_id')) {
                try {
                    $table->dropForeign(['elderly_id']);
                } catch (\Throwable $e) {
                    // Foreign key may not exist in some environments.
                }

                $table->foreign('elderly_id')
                    ->references('id')
                    ->on('users')
                    ->onDelete('cascade');
            }

            if (Schema::hasColumn('checklists', 'caregiver_id')) {
                try {
                    $table->dropForeign(['caregiver_id']);
                } catch (\Throwable $e) {
                    // Foreign key may not exist in some environments.
                }

                $table->foreign('caregiver_id')
                    ->references('id')
                    ->on('users')
                    ->onDelete('cascade');
            }
        });
    }
};
