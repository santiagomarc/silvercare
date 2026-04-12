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
        Schema::table('medications', function (Blueprint $table) {
            $table->string('purpose')->nullable()->after('instructions');
            $table->string('prescribing_doctor')->nullable()->after('purpose');
            $table->string('appearance_color')->nullable()->after('prescribing_doctor');
            $table->string('appearance_shape')->nullable()->after('appearance_color');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('medications', function (Blueprint $table) {
            $table->dropColumn([
                'purpose',
                'prescribing_doctor',
                'appearance_color',
                'appearance_shape',
            ]);
        });
    }
};
