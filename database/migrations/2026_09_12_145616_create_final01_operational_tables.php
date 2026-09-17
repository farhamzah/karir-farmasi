<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('career_application_documents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('career_job_application_id');
            $table->string('type', 30);
            $table->string('disk', 40)->default('private');
            $table->string('path');
            $table->string('original_name');
            $table->string('mime', 120);
            $table->unsignedBigInteger('size');
            $table->timestamp('uploaded_at');
            $table->timestamps();
            $table->foreign('career_job_application_id', 'app_doc_application_fk')
                ->references('id')->on('career_job_applications')->cascadeOnDelete();
            $table->unique(['career_job_application_id', 'type'], 'app_doc_application_type_unique');
        });

        Schema::create('career_notifications', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_reference')->unique();
            $table->string('recipient_type', 30);
            $table->string('recipient_reference', 191);
            $table->string('type', 80);
            $table->string('title');
            $table->text('body');
            $table->string('action_url', 2048)->nullable();
            $table->json('context')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->index(['recipient_type', 'recipient_reference', 'read_at'], 'notification_recipient_read_index');
        });

        $this->createImportTables();
        $this->createTracerTables();
        $this->createFeedbackTable();
    }

    private function createImportTables(): void
    {
        Schema::create('career_job_import_batches', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_reference')->unique();
            $table->string('actor_core_user_id', 191);
            $table->string('original_name');
            $table->string('status', 20)->default('preview');
            $table->unsignedInteger('valid_count')->default(0);
            $table->unsignedInteger('invalid_count')->default(0);
            $table->unsignedInteger('duplicate_count')->default(0);
            $table->timestamp('imported_at')->nullable();
            $table->timestamps();
        });

        Schema::create('career_job_import_rows', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('career_job_import_batch_id');
            $table->unsignedInteger('row_number');
            $table->json('payload');
            $table->json('errors')->nullable();
            $table->foreignId('possible_duplicate_job_id')->nullable();
            $table->foreignId('imported_job_id')->nullable();
            $table->timestamps();
            $table->unique(['career_job_import_batch_id', 'row_number'], 'job_import_batch_row_unique');
            $table->foreign('career_job_import_batch_id', 'job_import_row_batch_fk')
                ->references('id')->on('career_job_import_batches')->cascadeOnDelete();
            $table->foreign('possible_duplicate_job_id', 'job_import_row_duplicate_fk')
                ->references('id')->on('career_jobs')->nullOnDelete();
            $table->foreign('imported_job_id', 'job_import_row_imported_fk')
                ->references('id')->on('career_jobs')->nullOnDelete();
        });
    }

    private function createTracerTables(): void
    {
        Schema::create('tracer_periods', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_reference')->unique();
            $table->string('title');
            $table->string('cohort', 80);
            $table->string('program_reference', 120)->nullable();
            $table->string('faculty_reference', 120)->nullable();
            $table->date('starts_on');
            $table->date('ends_on');
            $table->string('status', 20)->default('draft');
            $table->string('created_by_core_user_id', 191);
            $table->timestamps();
            $table->index(['status', 'starts_on', 'ends_on']);
            $table->index(['program_reference', 'cohort']);
        });

        Schema::create('tracer_questionnaire_versions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tracer_period_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->json('questions');
            $table->timestamp('published_at')->nullable();
            $table->string('created_by_core_user_id', 191);
            $table->timestamps();
            $table->unique(['tracer_period_id', 'version']);
        });

        Schema::create('tracer_submissions', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_reference')->unique();
            $table->foreignId('tracer_period_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tracer_questionnaire_version_id');
            $table->foreignId('career_profile_id')->constrained()->cascadeOnDelete();
            $table->string('status', 20)->default('draft');
            $table->json('answers')->nullable();
            $table->json('profile_prefill')->nullable();
            $table->json('submission_snapshot')->nullable();
            $table->string('snapshot_checksum', 64)->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reopened_at')->nullable();
            $table->string('reopened_by_core_user_id', 191)->nullable();
            $table->timestamps();
            $table->unique(['tracer_questionnaire_version_id', 'career_profile_id'], 'tracer_version_profile_unique');
            $table->index(['tracer_period_id', 'status']);
            $table->foreign('tracer_questionnaire_version_id', 'tracer_submission_version_fk')
                ->references('id')->on('tracer_questionnaire_versions')->restrictOnDelete();
        });
    }

    private function createFeedbackTable(): void
    {
        Schema::create('employer_feedback', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_reference')->unique();
            $table->foreignId('career_job_application_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('career_profile_id')->constrained()->cascadeOnDelete();
            $table->string('relationship', 40);
            $table->json('ratings');
            $table->text('strengths')->nullable();
            $table->text('development_notes')->nullable();
            $table->text('comment')->nullable();
            $table->timestamp('submitted_at');
            $table->timestamps();
            $table->unique(['career_job_application_id', 'company_id'], 'feedback_application_company_unique');
            $table->index(['company_id', 'submitted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employer_feedback');
        Schema::dropIfExists('tracer_submissions');
        Schema::dropIfExists('tracer_questionnaire_versions');
        Schema::dropIfExists('tracer_periods');
        Schema::dropIfExists('career_job_import_rows');
        Schema::dropIfExists('career_job_import_batches');
        Schema::dropIfExists('career_notifications');
        Schema::dropIfExists('career_application_documents');
    }
};
