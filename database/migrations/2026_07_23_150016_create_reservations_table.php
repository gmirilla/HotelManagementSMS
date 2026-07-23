<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->restrictOnDelete();
            $table->foreignId('guest_id')->constrained()->restrictOnDelete();
            $table->foreignId('corporate_account_id')->nullable()->constrained()->nullOnDelete();
            $table->string('confirmation_code')->unique();
            $table->string('source')->default('walk_in');
            $table->string('status')->default('pending');
            $table->date('arrival_date');
            $table->date('departure_date');
            $table->unsignedTinyInteger('adults')->default(1);
            $table->unsignedTinyInteger('children')->default(0);
            $table->text('special_requests')->nullable();
            $table->unsignedBigInteger('cancellation_fee_cents')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['branch_id', 'status', 'arrival_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
