<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_opening_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('stage')->default('applied');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['job_opening_id', 'stage']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidates');
    }
};
