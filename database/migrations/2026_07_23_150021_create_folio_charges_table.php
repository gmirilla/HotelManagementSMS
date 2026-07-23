<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('folio_charges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('folio_id')->constrained()->cascadeOnDelete();
            $table->string('charge_type');
            $table->string('description')->nullable();
            $table->bigInteger('amount_cents');
            $table->date('charge_date');
            $table->foreignId('posted_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['folio_id', 'charge_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('folio_charges');
    }
};
