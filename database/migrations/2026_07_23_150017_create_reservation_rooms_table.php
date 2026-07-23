<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservation_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('room_type_id')->constrained()->restrictOnDelete();
            $table->foreignId('room_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('rate_cents');
            $table->unsignedTinyInteger('occupants_adults')->default(1);
            $table->unsignedTinyInteger('occupants_children')->default(0);
            $table->timestamps();

            $table->index(['room_id', 'room_type_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservation_rooms');
    }
};
