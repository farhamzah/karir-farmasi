<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cv_published_revisions', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('career_cv_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cv_template_version_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('revision_number');
            $table->json('snapshot');
            $table->string('content_checksum', 64);
            $table->string('photo_path')->nullable();
            $table->string('photo_mime', 100)->nullable();
            $table->timestamp('published_at');
            $table->timestamps();
            $table->unique(['career_cv_id', 'revision_number']);
            $table->index(['career_cv_id', 'published_at']);
        });

        Schema::create('cv_share_links', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('career_cv_id')->constrained()->cascadeOnDelete();
            $table->foreignId('current_revision_id')->constrained('cv_published_revisions')->cascadeOnDelete();
            $table->string('token_hash', 64)->unique();
            $table->text('token_ciphertext');
            $table->string('label')->nullable();
            $table->boolean('active')->default(true);
            $table->boolean('follow_latest_published')->default(true);
            $table->boolean('allow_pdf_download')->default(false);
            $table->timestamp('expires_at')->nullable();
            $table->unsignedBigInteger('view_count')->default(0);
            $table->timestamp('last_viewed_at')->nullable();
            $table->timestamp('rotated_at')->nullable();
            $table->timestamps();
            $table->index(['career_cv_id', 'active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cv_share_links');
        Schema::dropIfExists('cv_published_revisions');
    }
};
