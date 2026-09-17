<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cv_templates', function (Blueprint $table) {
            $table->id();
            $table->string('key', 30)->unique();
            $table->string('name');
            $table->text('description');
            $table->boolean('active')->default(true);
            $table->string('category', 50);
            $table->timestamps();
        });

        Schema::create('cv_template_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cv_template_id')->constrained()->restrictOnDelete();
            $table->string('version', 30);
            $table->json('configuration');
            $table->string('status', 30)->default('published');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->unique(['cv_template_id', 'version']);
        });

        Schema::create('career_cvs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('career_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cv_template_version_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('custom_headline')->nullable();
            $table->text('custom_summary')->nullable();
            $table->string('status', 20)->default('draft');
            $table->timestamps();
            $table->index(['career_profile_id', 'updated_at']);
        });

        Schema::create('cv_section_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('career_cv_id')->constrained()->cascadeOnDelete();
            $table->string('section_key', 40);
            $table->boolean('enabled')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->string('display_title')->nullable();
            $table->timestamps();
            $table->unique(['career_cv_id', 'section_key'], 'cv_section_key_unique');
            $table->index(['career_cv_id', 'enabled', 'sort_order'], 'cv_section_active_order');
        });

        Schema::create('cv_item_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('career_cv_id')->constrained()->cascadeOnDelete();
            $table->string('section_key', 40);
            $table->unsignedBigInteger('source_item_id');
            $table->boolean('enabled')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['career_cv_id', 'section_key', 'source_item_id'], 'cv_item_source_unique');
            $table->index(['career_cv_id', 'enabled', 'sort_order'], 'cv_item_active_order');
        });

        $now = now();
        $formalId = DB::table('cv_templates')->insertGetId([
            'key' => 'cv-01', 'name' => 'Klasik Formal',
            'description' => 'Komposisi satu kolom yang tenang, jelas, dan mudah dipindai.',
            'active' => true, 'category' => 'formal', 'created_at' => $now, 'updated_at' => $now,
        ]);
        $photoId = DB::table('cv_templates')->insertGetId([
            'key' => 'cv-02', 'name' => 'Profesional Foto',
            'description' => 'Komposisi visual dua area dengan foto proporsional dan ritme modern.',
            'active' => true, 'category' => 'professional', 'created_at' => $now, 'updated_at' => $now,
        ]);

        DB::table('cv_template_versions')->insert([
            [
                'cv_template_id' => $formalId, 'version' => '1.0.0',
                'configuration' => json_encode(['layout' => 'single-column', 'photo' => 'hidden', 'typography' => 'classic', 'spacing' => 'comfortable', 'section_style' => 'rule'], JSON_THROW_ON_ERROR),
                'status' => 'published', 'published_at' => $now, 'created_at' => $now, 'updated_at' => $now,
            ],
            [
                'cv_template_id' => $photoId, 'version' => '1.0.0',
                'configuration' => json_encode(['layout' => 'sidebar-main', 'photo' => 'circle', 'typography' => 'modern', 'spacing' => 'balanced', 'section_style' => 'accent'], JSON_THROW_ON_ERROR),
                'status' => 'published', 'published_at' => $now, 'created_at' => $now, 'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('cv_item_preferences');
        Schema::dropIfExists('cv_section_preferences');
        Schema::dropIfExists('career_cvs');
        Schema::dropIfExists('cv_template_versions');
        Schema::dropIfExists('cv_templates');
    }
};
