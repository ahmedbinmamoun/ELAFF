<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
 * FR-ISS-01, 02, 06 (PRD §7). Beyond the PRD columns: the cover thumbnail and
 * social image paths, page image sizes (the viewer reserves space), the
 * download counter and when processing finished.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('issue_id_sequence', static function (Blueprint $table): void {
            $table->id();
        });

        Schema::create('issues', static function (Blueprint $table): void {
            $table->unsignedBigInteger('id')->primary();
            $table->unsignedInteger('number')->unique();
            $table->date('published_on')->index();
            $table->string('title', 200)->nullable();
            $table->string('pdf_path');
            $table->unsignedBigInteger('pdf_size');
            $table->char('pdf_checksum', 64);
            $table->string('cover_path')->nullable();
            $table->string('cover_thumb_path')->nullable();
            $table->string('social_image_path')->nullable();
            $table->unsignedSmallInteger('page_count')->nullable();
            $table->string('processing_status', 20)->index();
            $table->text('processing_error')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->unsignedBigInteger('downloads_count')->default(0);
            $table->timestamps();

            $table->index(['processing_status', 'number']);
        });

        Schema::create('issue_pages', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('issue_id')->constrained('issues')->cascadeOnDelete();
            $table->unsignedSmallInteger('page_number');
            $table->string('image_path');
            $table->string('thumb_path');
            $table->unsignedSmallInteger('width');
            $table->unsignedSmallInteger('height');
            $table->timestamps();

            $table->unique(['issue_id', 'page_number']);
        });

        // Articles could only point at issues that did not exist yet.
        DB::table('articles')->whereNotNull('issue_id')->update(['issue_id' => null, 'issue_page' => null]);

        Schema::table('articles', static function (Blueprint $table): void {
            $table->foreign('issue_id')->references('id')->on('issues')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('articles', static function (Blueprint $table): void {
            $table->dropForeign(['issue_id']);
        });

        Schema::dropIfExists('issue_pages');
        Schema::dropIfExists('issues');
        Schema::dropIfExists('issue_id_sequence');
    }
};
