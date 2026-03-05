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
        Schema::table('checklists', function (Blueprint $table) {
            if (!Schema::hasColumn('checklists', 'priority')) {
                $table->string('priority')->default('medium')->after('due_time');
            }
            if (!Schema::hasColumn('checklists', 'notes')) {
                $table->text('notes')->nullable()->after('priority');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('checklists', function (Blueprint $table) {
            $table->dropColumn(['priority', 'notes']);
        });
    }
};
