<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->string('sku');
            $table->string('name');
            $table->string('unit_of_measure');
            $table->string('barcode')->nullable();
            $table->integer('reorder_point')->default(0);
            $table->integer('quantity_on_hand')->default(0);
            $table->unsignedBigInteger('average_cost_cents')->default(0);
            $table->date('expires_on')->nullable();
            $table->boolean('is_perishable')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['warehouse_id', 'sku']);
            $table->index(['warehouse_id', 'quantity_on_hand']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_items');
    }
};
