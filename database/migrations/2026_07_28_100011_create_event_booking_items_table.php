<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_booking_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_service_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('quantity')->default(1);
            $table->integer('unit_price_cents');
            $table->timestamps();

            $table->index('event_booking_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_booking_items');
    }
};
