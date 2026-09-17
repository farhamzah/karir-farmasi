<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('career_jobs', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_reference')->unique();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->string('created_by_type', 30);
            $table->string('created_by_reference', 191);
            $table->string('employer_display_name');
            $table->string('title');
            $table->string('employment_type', 30);
            $table->string('work_mode', 20);
            $table->string('city', 120)->nullable();
            $table->string('location_text')->nullable();
            $table->text('description');
            $table->text('requirements')->nullable();
            $table->text('responsibilities')->nullable();
            $table->string('education_requirement')->nullable();
            $table->string('experience_requirement')->nullable();
            $table->unsignedBigInteger('salary_min')->nullable();
            $table->unsignedBigInteger('salary_max')->nullable();
            $table->boolean('salary_visible')->default(false);
            $table->unsignedInteger('openings')->nullable();
            $table->string('status', 20)->default('draft');
            $table->string('application_method', 30);
            $table->string('external_apply_url', 2048)->nullable();
            $table->string('external_apply_email')->nullable();
            $table->text('application_instruction')->nullable();
            $table->string('source_type', 40);
            $table->string('source_name');
            $table->string('source_reference', 2048)->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamp('source_verified_at')->nullable();
            $table->string('source_verified_by')->nullable();
            $table->text('internal_notes')->nullable();
            $table->string('source_attachment_path')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('review_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('duplicate_of_job_id')->nullable()->constrained('career_jobs')->nullOnDelete();
            $table->boolean('possible_duplicate')->default(false);
            $table->string('reviewed_by')->nullable();
            $table->text('review_note')->nullable();
            $table->timestamps();
            $table->index(['status', 'published_at', 'expires_at']);
            $table->index(['company_id', 'status']);
            $table->index(['employer_display_name', 'title']);
        });

        Schema::create('career_job_tags', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('career_job_id')->constrained()->cascadeOnDelete();
            $table->string('category', 40);
            $table->string('label', 100);
            $table->string('normalized_label', 100);
            $table->timestamps();
            $table->unique(['career_job_id', 'category', 'normalized_label'], 'job_tag_unique');
            $table->index(['category', 'normalized_label']);
        });

        Schema::create('career_job_applications', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_reference')->unique();
            $table->foreignId('career_job_id')->constrained()->cascadeOnDelete();
            $table->foreignId('career_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('career_cv_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('cv_template_version_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('method', 30);
            $table->string('status', 30)->default('draft');
            $table->json('cv_snapshot')->nullable();
            $table->string('snapshot_checksum', 64)->nullable();
            $table->text('cover_letter')->nullable();
            $table->string('supporting_document_path')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('withdrawn_at')->nullable();
            $table->timestamps();
            $table->unique(['career_job_id', 'career_profile_id']);
            $table->index(['career_job_id', 'status']);
            $table->index(['career_profile_id', 'status']);
        });

        Schema::create('career_application_transitions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('career_job_application_id')->constrained()->cascadeOnDelete();
            $table->string('from_status', 30)->nullable();
            $table->string('to_status', 30);
            $table->string('actor_type', 30);
            $table->string('actor_reference', 191);
            $table->text('note')->nullable();
            $table->timestamp('created_at');
            $table->index(['career_job_application_id', 'created_at'], 'application_transition_time_index');
        });

        Schema::create('career_job_invitations', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_reference')->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('career_job_id')->constrained()->cascadeOnDelete();
            $table->foreignId('career_profile_id')->constrained()->cascadeOnDelete();
            $table->string('message', 500)->nullable();
            $table->string('status', 30)->default('sent');
            $table->timestamp('viewed_at')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            $table->unique(['career_job_id', 'career_profile_id']);
        });

        Schema::create('career_job_external_actions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('career_job_id')->constrained()->cascadeOnDelete();
            $table->foreignId('career_profile_id')->constrained()->cascadeOnDelete();
            $table->string('action', 40);
            $table->timestamp('occurred_at');
            $table->timestamps();
            $table->index(['career_job_id', 'career_profile_id', 'action'], 'job_external_action_index');
        });

        Schema::create('career_job_bookmarks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('career_job_id')->constrained()->cascadeOnDelete();
            $table->foreignId('career_profile_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['career_job_id', 'career_profile_id']);
        });

        Schema::create('career_job_reports', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('career_job_id')->constrained()->cascadeOnDelete();
            $table->foreignId('career_profile_id')->constrained()->cascadeOnDelete();
            $table->string('reason', 100);
            $table->text('detail')->nullable();
            $table->string('status', 20)->default('open');
            $table->timestamps();
            $table->unique(['career_job_id', 'career_profile_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('career_job_reports');
        Schema::dropIfExists('career_job_bookmarks');
        Schema::dropIfExists('career_job_external_actions');
        Schema::dropIfExists('career_job_invitations');
        Schema::dropIfExists('career_application_transitions');
        Schema::dropIfExists('career_job_applications');
        Schema::dropIfExists('career_job_tags');
        Schema::dropIfExists('career_jobs');
    }
};
