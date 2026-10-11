<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * FR-ART-01..08. issue_id gets its foreign key with the issues table (Phase 4);
 * search_text and the FULLTEXT index come with search (Phase 6).
 */
return new class extends Migration
{
    public function up(): void
    {
        // Hands out article ids before the row exists (ADR-0002).
        Schema::create('article_id_sequence', static function (Blueprint $table): void {
            $table->id();
        });

        Schema::create('articles', static function (Blueprint $table): void {
            $table->unsignedBigInteger('id')->primary();
            $table->string('origin', 20);
            $table->foreignId('category_id')->constrained('categories')->restrictOnDelete();
            $table->foreignId('series_id')->nullable()->constrained('series')->nullOnDelete();
            $table->unsignedInteger('series_part')->nullable();
            $table->unsignedBigInteger('issue_id')->nullable()->index();
            $table->unsignedInteger('issue_page')->nullable();
            $table->string('kicker')->nullable();
            $table->string('title');
            $table->string('subtitle', 500)->nullable();
            $table->string('slug', 150);
            $table->string('dateline', 100)->nullable();
            $table->text('excerpt')->nullable();
            $table->longText('body');
            $table->longText('search_text')->nullable();
            $table->string('featured_image_path')->nullable();
            $table->string('featured_image_caption', 500)->nullable();
            $table->string('featured_image_credit')->nullable();
            $table->string('source_name', 120);
            $table->string('source_url', 2048)->nullable();
            $table->string('status', 20)->index();
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamp('scheduled_for')->nullable();
            $table->boolean('is_breaking')->default(false);
            $table->timestamp('breaking_until')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->unsignedInteger('homepage_priority')->default(0);
            $table->unsignedBigInteger('views_count')->default(0);
            $table->unsignedSmallInteger('reading_minutes')->default(1);
            $table->string('seo_title')->nullable();
            $table->string('seo_description', 500)->nullable();
            $table->string('canonical_url', 2048)->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->text('return_note')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'published_at']);
            $table->index(['category_id', 'status', 'published_at']);
            $table->index(['status', 'scheduled_for']);
            $table->index(['is_breaking', 'status']);
            $table->index(['series_id', 'series_part']);
        });

        Schema::create('article_author', static function (Blueprint $table): void {
            $table->foreignId('article_id')->constrained('articles')->cascadeOnDelete();
            $table->foreignId('author_id')->constrained('authors')->restrictOnDelete();
            $table->unsignedSmallInteger('position')->default(0);
            $table->primary(['article_id', 'author_id']);
            $table->index('author_id');
        });

        Schema::create('article_tag', static function (Blueprint $table): void {
            $table->foreignId('article_id')->constrained('articles')->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained('tags')->cascadeOnDelete();
            $table->primary(['article_id', 'tag_id']);
            $table->index('tag_id');
        });

        Schema::create('article_revisions', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('article_id')->constrained('articles')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->json('snapshot');
            $table->timestamp('created_at');
            $table->index(['article_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('article_revisions');
        Schema::dropIfExists('article_tag');
        Schema::dropIfExists('article_author');
        Schema::dropIfExists('articles');
        Schema::dropIfExists('article_id_sequence');
    }
};
