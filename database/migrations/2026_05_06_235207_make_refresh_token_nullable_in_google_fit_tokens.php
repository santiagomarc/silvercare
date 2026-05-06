<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Make refresh_token nullable.
     *
     * The original schema defined refresh_token as text NOT NULL, but
     * GoogleFitService::storeTokens() stores null when Google omits the
     * refresh_token (e.g. user had previously authorised without revoking).
     * A NULL refresh_token is handled gracefully by refreshAccessToken().
     */
    public function up(): void
    {
        Schema::table('google_fit_tokens', function (Blueprint $table) {
            $table->text('refresh_token')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('google_fit_tokens', function (Blueprint $table) {
            $table->text('refresh_token')->nullable(false)->change();
        });
    }
};
