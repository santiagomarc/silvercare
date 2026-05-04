<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->timestamp('archived_at')->nullable()->after('last_login_at');
            $table->unsignedBigInteger('archived_by_caregiver_id')->nullable()->after('archived_at');
        });
    }

    public function down(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->dropColumn(['archived_at', 'archived_by_caregiver_id']);
        });
    }
};