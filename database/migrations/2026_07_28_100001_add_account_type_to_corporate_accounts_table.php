<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('corporate_accounts', function (Blueprint $table) {
            $table->string('account_type')->default('corporate')->after('company_name');
            $table->decimal('commission_percent', 5, 2)->nullable()->after('negotiated_rate_cents');
        });
    }

    public function down(): void
    {
        Schema::table('corporate_accounts', function (Blueprint $table) {
            $table->dropColumn(['account_type', 'commission_percent']);
        });
    }
};
