<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * H1 + H2 + H5 — make dose state changes reversible and idempotent.
 *
 * H1: confirmDose skipped the stock decrement when current_stock was already 0,
 *     but undoDose incremented unconditionally. Confirming at zero stock and
 *     then undoing invented a pill that never existed. Recording what the
 *     confirm actually did lets undo reverse exactly that and nothing more.
 *
 * H2: idempotency_key was globally unique. Replaying a key against a different
 *     dose instance fell through to an UPDATE that violated the index and
 *     surfaced as an unhandled 500 — the exact scenario a flaky mobile
 *     connection produces. Scoping the key per patient makes collisions between
 *     unrelated patients impossible, and the service now returns a 409 for a
 *     genuine reuse within one patient.
 *
 * H5: hold and skip need a reason recorded against the dose, so a caregiver can
 *     later see why a dose was not administered.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('medication_dose_instances', function (Blueprint $table) {
            // How many units the confirm actually removed from stock (0 when
            // the medication is untracked or stock was already exhausted).
            // Undo returns exactly this, so it can never mint inventory.
            $table->smallInteger('inventory_delta')->default(0)->after('source');

            // Why a dose was held or skipped.
            $table->string('state_reason')->nullable()->after('notes');

            // Who put it in that state, and when.
            $table->timestamp('state_changed_at')->nullable()->after('state_reason');
        });

        // H2: replace the global unique index on idempotency_key with one
        // scoped per patient.
        Schema::table('medication_dose_instances', function (Blueprint $table) {
            $table->dropUnique('medication_dose_instances_idempotency_key_unique');
        });

        Schema::table('medication_dose_instances', function (Blueprint $table) {
            $table->unique(['elderly_id', 'idempotency_key'], 'dose_instances_elderly_idempotency_unique');
        });

        // Existing taken doses predate inventory_delta. Assume a tracked
        // medication decremented by one, which is what the old code did
        // whenever stock was above zero; untracked ones stay at 0.
        DB::table('medication_dose_instances')
            ->whereIn('state', ['taken', 'taken_late'])
            ->whereIn('medication_id', function ($query) {
                $query->select('id')->from('medications')->where('track_inventory', true);
            })
            ->update(['inventory_delta' => 1]);
    }

    public function down(): void
    {
        Schema::table('medication_dose_instances', function (Blueprint $table) {
            $table->dropUnique('dose_instances_elderly_idempotency_unique');
        });

        Schema::table('medication_dose_instances', function (Blueprint $table) {
            $table->unique('idempotency_key');
            $table->dropColumn(['inventory_delta', 'state_reason', 'state_changed_at']);
        });
    }
};
