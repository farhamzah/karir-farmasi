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
        Schema::create('career_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('core_user_id')->unique();
            $table->string('professional_name')->nullable();
            $table->string('headline')->nullable();
            $table->text('professional_summary')->nullable();
            $table->string('professional_email')->nullable();
            $table->string('whatsapp', 30)->nullable();
            $table->string('city')->nullable();
            $table->string('linkedin_url')->nullable();
            $table->string('portfolio_url')->nullable();
            $table->string('photo_path')->nullable();
            $table->boolean('open_to_work')->default(false);
            $table->string('profile_visibility', 20)->default('private');
            $table->json('section_visibility')->nullable();
            $table->timestamp('last_confirmed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('career_educations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('career_profile_id')->constrained()->cascadeOnDelete();
            $table->string('institution_name');
            $table->string('program_name');
            $table->string('degree', 100)->nullable();
            $table->unsignedSmallInteger('start_year')->nullable();
            $table->unsignedSmallInteger('end_year')->nullable();
            $table->string('status', 30)->nullable();
            $table->string('source', 30)->default('user_declared');
            $table->string('source_reference')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_visible')->default(true);
            $table->timestamps();
            $table->index(['career_profile_id', 'sort_order']);
            $table->index(['program_name', 'degree']);
        });

        Schema::create('career_experiences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('career_profile_id')->constrained()->cascadeOnDelete();
            $table->string('type', 30);
            $table->string('organization');
            $table->string('title');
            $table->string('location')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->boolean('currently_active')->default(false);
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_visible')->default(true);
            $table->timestamps();
            $table->index(['career_profile_id', 'sort_order']);
            $table->index(['type', 'organization']);
        });

        Schema::create('career_skills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('career_profile_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('category')->nullable();
            $table->string('level', 50)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_visible')->default(true);
            $table->timestamps();
            $table->index(['career_profile_id', 'sort_order']);
            $table->index(['name', 'category']);
        });

        Schema::create('career_certifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('career_profile_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('issuer');
            $table->date('issue_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('credential_id')->nullable();
            $table->string('credential_url')->nullable();
            $table->string('attachment_path')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_visible')->default(true);
            $table->timestamps();
            $table->index(['career_profile_id', 'sort_order']);
        });

        Schema::create('career_organizations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('career_profile_id')->constrained()->cascadeOnDelete();
            $table->string('organization');
            $table->string('role');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_visible')->default(true);
            $table->timestamps();
            $table->index(['career_profile_id', 'sort_order']);
        });

        Schema::create('career_projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('career_profile_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('category')->nullable();
            $table->text('description')->nullable();
            $table->string('project_url')->nullable();
            $table->string('attachment_path')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_visible')->default(true);
            $table->timestamps();
            $table->index(['career_profile_id', 'sort_order']);
        });

        Schema::create('career_publications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('career_profile_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('publication_name')->nullable();
            $table->date('published_on')->nullable();
            $table->string('url')->nullable();
            $table->string('doi')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_visible')->default(true);
            $table->timestamps();
            $table->index(['career_profile_id', 'sort_order']);
        });

        Schema::create('career_languages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('career_profile_id')->constrained()->cascadeOnDelete();
            $table->string('language');
            $table->string('proficiency', 50)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_visible')->default(true);
            $table->timestamps();
            $table->index(['career_profile_id', 'sort_order']);
        });

        Schema::create('career_job_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('career_profile_id')->unique()->constrained()->cascadeOnDelete();
            $table->json('target_roles')->nullable();
            $table->json('employment_types')->nullable();
            $table->json('preferred_locations')->nullable();
            $table->boolean('willing_to_relocate')->default(false);
            $table->date('availability_date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('career_job_preferences');
        Schema::dropIfExists('career_languages');
        Schema::dropIfExists('career_publications');
        Schema::dropIfExists('career_projects');
        Schema::dropIfExists('career_organizations');
        Schema::dropIfExists('career_certifications');
        Schema::dropIfExists('career_skills');
        Schema::dropIfExists('career_experiences');
        Schema::dropIfExists('career_educations');
        Schema::dropIfExists('career_profiles');
    }
};
