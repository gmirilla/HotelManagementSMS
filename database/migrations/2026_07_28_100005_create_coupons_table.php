<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->string('code');
            $table->string('name');
            $table->string('discount_type');
            $table->unsignedInteger('discount_value');
            $table->string('scope')->default('all');
            $table->date('valid_from');
            $table->date('valid_until');
            $table->unsignedInteger('usage_limit')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['branch_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};
