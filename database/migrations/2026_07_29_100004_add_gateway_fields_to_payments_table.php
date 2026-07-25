<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->timestamp('paid_at')->nullable()->after('status');
            // Nullable-safe: MySQL/SQLite both allow multiple NULLs under a
            // unique index, so existing manual-payment rows (which never set
            // gateway_reference) are unaffected. Makes the idempotency
            // guarantee (one Payment row per gateway reference) enforceable
            // at the DB layer, not just in ConfirmGatewayPaymentAction.
            $table->unique('gateway_reference');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropUnique(['gateway_reference']);
            $table->dropColumn('paid_at');
        });
    }
};
