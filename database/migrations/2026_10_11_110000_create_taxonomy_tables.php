<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * FR-TAX-01..03, FR-AUT-01.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('name', 120);
            $table->string('slug', 150)->unique();
            $table->text('description')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_visible')->default(true);
            $table->boolean('is_open_to_contributors')->default(false);
            $table->string('seo_title')->nullable();
            $table->string('seo_description', 500)->nullable();
            $table->timestamps();
            $table->index(['is_visible', 'sort_order']);
        });

        Schema::create('tags', static function (Blueprint $table): void {
            $table->id();
            $table->string('name', 60);
            $table->string('slug', 150)->unique();
            $table->timestamps();
            $table->index('name');
        });

        Schema::create('series', static function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('slug', 150)->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('authors', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->unique()->constrained('users')->nullOnDelete();
            $table->string('name', 120);
            $table->string('slug', 150)->unique();
            $table->string('title', 120)->nullable();
            $table->text('bio')->nullable();
            $table->string('photo_path')->nullable();
            $table->boolean('is_columnist')->default(false)->index();
            $table->json('social_links')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('authors');
        Schema::dropIfExists('series');
        Schema::dropIfExists('tags');
        Schema::dropIfExists('categories');
    }
};
