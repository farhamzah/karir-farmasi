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
        Schema::table('career_profiles', function (Blueprint $table) {
            $table->uuid('talent_reference')->nullable()->unique()->after('core_user_id');
            $table->boolean('discoverable_by_verified_companies')->default(false)->after('profile_visibility');
            $table->boolean('discoverable_by_internal_leadership')->default(false)->after('discoverable_by_verified_companies');
            $table->timestamp('discoverability_updated_at')->nullable()->after('discoverable_by_internal_leadership');
            $table->index(['discoverable_by_verified_companies', 'open_to_work'], 'career_profiles_external_search');
            $table->index(['discoverable_by_internal_leadership', 'open_to_work'], 'career_profiles_internal_search');
        });

        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_reference')->unique();
            $table->string('legal_name');
            $table->string('display_name');
            $table->string('business_sector');
            $table->string('company_size', 40)->nullable();
            $table->string('website')->nullable();
            $table->string('city');
            $table->text('description')->nullable();
            $table->string('verification_status', 20)->default('pending');
            $table->timestamp('verified_at')->nullable();
            $table->string('verified_by_core_user_id')->nullable();
            $table->text('decision_note')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->index(['verification_status', 'active']);
        });

        Schema::create('company_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->uuid('public_reference')->unique();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('role', 30)->default('recruiter');
            $table->boolean('active')->default(true);
            $table->timestamp('last_login_at')->nullable();
            $table->timestamps();
            $table->index(['company_id', 'active']);
        });

        Schema::create('leadership_assignments', function (Blueprint $table) {
            $table->id();
            $table->string('actor_core_user_id');
            $table->string('scope_type', 20);
            $table->string('scope_reference');
            $table->string('scope_label');
            $table->json('program_references')->nullable();
            $table->string('role_label', 40);
            $table->boolean('active')->default(true);
            $table->date('valid_from')->nullable();
            $table->date('valid_until')->nullable();
            $table->timestamps();
            $table->unique(['actor_core_user_id', 'scope_type', 'scope_reference'], 'leadership_actor_scope_unique');
            $table->index(['actor_core_user_id', 'active']);
        });

        Schema::create('talent_profile_indexes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('career_profile_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('professional_name');
            $table->string('headline')->nullable();
            $table->string('city')->nullable();
            $table->string('education_level', 80)->nullable();
            $table->string('education_program')->nullable();
            $table->unsignedSmallInteger('graduation_year')->nullable();
            $table->json('experience_types')->nullable();
            $table->json('sectors')->nullable();
            $table->json('skills')->nullable();
            $table->json('certifications')->nullable();
            $table->json('event_topics')->nullable();
            $table->json('event_roles')->nullable();
            $table->json('project_tags')->nullable();
            $table->json('publication_keywords')->nullable();
            $table->json('languages')->nullable();
            $table->json('target_roles')->nullable();
            $table->json('preferred_locations')->nullable();
            $table->longText('normalized_terms');
            $table->boolean('open_to_work')->default(false);
            $table->boolean('willing_to_relocate')->default(false);
            $table->date('availability_date')->nullable();
            $table->timestamp('last_confirmed_at')->nullable();
            $table->timestamps();
            $table->index(['education_program', 'graduation_year']);
            $table->index(['city', 'open_to_work']);
        });

        Schema::create('company_shortlists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('career_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('saved_by_company_user_id')->constrained('company_users')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['company_id', 'career_profile_id']);
        });

        Schema::create('talent_access_audits', function (Blueprint $table) {
            $table->id();
            $table->string('actor_type', 20);
            $table->string('actor_reference');
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 40);
            $table->uuid('target_reference')->nullable();
            $table->char('query_fingerprint', 64)->nullable();
            $table->unsignedInteger('result_count')->nullable();
            $table->json('filter_keys')->nullable();
            $table->timestamp('created_at');
            $table->index(['actor_type', 'actor_reference', 'created_at'], 'talent_audit_actor_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('talent_access_audits');
        Schema::dropIfExists('company_shortlists');
        Schema::dropIfExists('talent_profile_indexes');
        Schema::dropIfExists('leadership_assignments');
        Schema::dropIfExists('company_users');
        Schema::dropIfExists('companies');
        Schema::table('career_profiles', function (Blueprint $table) {
            $table->dropIndex('career_profiles_external_search');
            $table->dropIndex('career_profiles_internal_search');
            $table->dropUnique(['talent_reference']);
            $table->dropColumn([
                'talent_reference', 'discoverable_by_verified_companies',
                'discoverable_by_internal_leadership', 'discoverability_updated_at',
            ]);
        });
    }
};
