<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * UI redesign: an optional icon per section (navigation) and the printed
 * paper's section an article appeared in.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', static function (Blueprint $table): void {
            $table->string('icon', 40)->nullable()->after('slug');
        });

        Schema::table('articles', static function (Blueprint $table): void {
            $table->string('print_section', 60)->nullable()->after('issue_page');
        });
    }

    public function down(): void
    {
        Schema::table('articles', static function (Blueprint $table): void {
            $table->dropColumn('print_section');
        });

        Schema::table('categories', static function (Blueprint $table): void {
            $table->dropColumn('icon');
        });
    }
};
