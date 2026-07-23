<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tax_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->decimal('rate_percent', 5, 2);
            $table->string('applies_to')->default('all');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['branch_id', 'applies_to']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_rules');
    }
};
