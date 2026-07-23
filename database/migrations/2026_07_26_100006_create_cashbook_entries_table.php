<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cashbook_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cashier_user_id')->constrained('users')->restrictOnDelete();
            $table->string('entry_type');
            $table->unsignedBigInteger('amount_cents');
            $table->string('reason')->nullable();
            $table->date('shift_date');
            $table->boolean('reconciled')->default(false);
            $table->timestamps();

            $table->index(['branch_id', 'shift_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cashbook_entries');
    }
};
