<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tenant::paystack_secret_key is cast 'encrypted', but the original
 * migration defined it as a plain string (VARCHAR 255) — Laravel's
 * encrypted cast produces a base64-encoded iv+value+mac payload roughly
 * 3-4x longer than the plaintext, which overflows VARCHAR(255) for any
 * real Paystack secret key. Widen it to TEXT, matching every other
 * encrypted-cast column in this codebase (guest_documents.document_number,
 * employees.national_id, employee_documents.reference_number,
 * users.mfa_secret/mfa_recovery_codes all already use text()).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->text('paystack_secret_key')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('paystack_secret_key')->nullable()->change();
        });
    }
};
