<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('disciplinary_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reported_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->date('incident_date');
            $table->string('severity');
            $table->text('description');
            $table->text('action_taken')->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'incident_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disciplinary_records');
    }
};
