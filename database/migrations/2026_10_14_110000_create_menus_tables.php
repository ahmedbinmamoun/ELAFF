<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * FR-NAV-01 (PRD §7 `menus`, `menu_items`). target_id points at a category or
 * a page depending on type, so it has no foreign key: the public query drops
 * items whose target is gone or hidden. icon is optional (section icons).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menus', static function (Blueprint $table): void {
            $table->id();
            $table->string('key', 30)->unique();
            $table->timestamps();
        });

        Schema::create('menu_items', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('menu_id')->constrained('menus')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('menu_items')->cascadeOnDelete();
            $table->string('label', 60);
            $table->string('type', 20);
            $table->unsignedBigInteger('target_id')->nullable();
            $table->string('url', 2048)->nullable();
            $table->string('icon', 40)->nullable();
            $table->unsignedSmallInteger('position');
            $table->boolean('is_visible')->default(true);
            $table->timestamps();

            $table->index(['menu_id', 'parent_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_items');
        Schema::dropIfExists('menus');
    }
};
