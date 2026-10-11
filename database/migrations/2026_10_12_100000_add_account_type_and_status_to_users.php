<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * PRD §7 users.type / users.status (FR-CON-02, 03, 12).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', static function (Blueprint $table): void {
            $table->string('type', 20)->default('staff')->after('password')->index();
            $table->string('status', 20)->default('active')->after('type')->index();
            $table->timestamp('last_login_at')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('users', static function (Blueprint $table): void {
            $table->dropIndex(['type']);
            $table->dropIndex(['status']);
            $table->dropColumn(['type', 'status', 'last_login_at']);
        });
    }
};
