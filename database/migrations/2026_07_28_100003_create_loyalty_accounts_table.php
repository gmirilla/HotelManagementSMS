<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Points balance and tier are never stored here — both are always
     * derived from loyalty_transactions (see LoyaltyBalanceCalculator), the
     * same ledger-truth pattern used for folio/inventory/account balances.
     */
    public function up(): void
    {
        Schema::create('loyalty_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guest_id')->unique()->constrained()->cascadeOnDelete();
            $table->date('enrolled_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loyalty_accounts');
    }
};
