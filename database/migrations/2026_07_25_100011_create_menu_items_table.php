<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menu_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_category_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->unsignedBigInteger('price_cents');
            $table->string('tax_class')->default('standard');
            $table->boolean('is_available')->default(true);
            $table->timestamps();

            $table->index('menu_category_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_items');
    }
};
