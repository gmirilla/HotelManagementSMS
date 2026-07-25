<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('paystack_public_key')->nullable()->after('logo_path');
            $table->string('paystack_secret_key')->nullable()->after('paystack_public_key');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['paystack_public_key', 'paystack_secret_key']);
        });
    }
};
