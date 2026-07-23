<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guest_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guest_id')->constrained()->cascadeOnDelete();
            $table->string('document_type');
            $table->text('document_number');
            $table->string('issuing_country')->nullable();
            $table->date('expires_on')->nullable();
            $table->timestamps();

            $table->index(['guest_id', 'document_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guest_documents');
    }
};
