<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * FR-ADS-01..03 (PRD §7 `ads`). Ids come from a sequence table like
 * articles and issues (the aggregate needs its id before it is saved).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ad_id_sequence', static function (Blueprint $table): void {
            $table->id();
        });

        Schema::create('ads', static function (Blueprint $table): void {
            $table->unsignedBigInteger('id')->primary();
            $table->string('title', 150);
            $table->string('slot', 40);
            $table->string('image_path')->nullable();
            $table->text('html')->nullable();
            $table->string('target_url', 2048)->nullable();
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('ends_at')->nullable();
            $table->unsignedTinyInteger('weight')->default(1);
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('clicks_count')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'slot']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ads');
        Schema::dropIfExists('ad_id_sequence');
    }
};
