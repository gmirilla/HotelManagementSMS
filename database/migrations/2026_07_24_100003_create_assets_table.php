<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('serial_number')->nullable();
            $table->date('purchased_on')->nullable();
            $table->date('warranty_expires_on')->nullable();
            $table->string('location')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('branch_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
