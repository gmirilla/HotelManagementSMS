<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->foreignId('current_branch_id')->nullable()->after('tenant_id')->constrained('branches')->nullOnDelete();
            $table->boolean('mfa_enabled')->default(false)->after('password');
            $table->text('mfa_secret')->nullable()->after('mfa_enabled');
            $table->timestamp('password_changed_at')->nullable()->after('mfa_secret');
            $table->unsignedInteger('failed_login_attempts')->default(0)->after('password_changed_at');
            $table->timestamp('locked_until')->nullable()->after('failed_login_attempts');
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('tenant_id');
            $table->dropConstrainedForeignId('current_branch_id');
            $table->dropColumn([
                'mfa_enabled',
                'mfa_secret',
                'password_changed_at',
                'failed_login_attempts',
                'locked_until',
                'deleted_at',
            ]);
        });
    }
};
