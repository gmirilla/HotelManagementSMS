<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('category');
            $table->integer('unit_price_cents');
            $table->string('unit')->default('flat');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['branch_id', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_services');
    }
};
