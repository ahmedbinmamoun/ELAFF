<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * FR-LET-01..03 (PRD §7 `reader_letters`), plus who moderated and when the
 * letter arrived. The IP is stored only as a keyed hash.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reader_letter_id_sequence', static function (Blueprint $table): void {
            $table->id();
        });

        Schema::create('reader_letters', static function (Blueprint $table): void {
            $table->unsignedBigInteger('id')->primary();
            $table->string('name', 100);
            $table->string('email', 190);
            $table->string('phone', 30)->nullable();
            $table->string('title', 150);
            $table->text('body');
            $table->string('status', 20)->index();
            $table->text('moderator_note')->nullable();
            $table->foreignId('moderated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('article_id')->nullable();
            $table->foreign('article_id')->references('id')->on('articles')->nullOnDelete();
            $table->char('ip_hash', 64);
            $table->dateTime('received_at')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reader_letters');
        Schema::dropIfExists('reader_letter_id_sequence');
    }
};
