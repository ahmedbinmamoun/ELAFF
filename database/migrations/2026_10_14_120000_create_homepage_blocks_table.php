<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * FR-HOME-01 (PRD §7 `homepage_blocks`), plus the region the block sits in.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('homepage_blocks', static function (Blueprint $table): void {
            $table->id();
            $table->string('type', 40);
            $table->string('region', 20);
            $table->json('config');
            $table->unsignedSmallInteger('position');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('homepage_blocks');
    }
};
