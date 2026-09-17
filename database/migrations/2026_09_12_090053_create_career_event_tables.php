<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('career_events', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('reference', 40)->unique();
            $table->string('event_type', 30);
            $table->string('organizer');
            $table->text('description');
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->string('location_type', 20);
            $table->string('location_text')->nullable();
            $table->unsignedInteger('capacity')->nullable();
            $table->dateTime('registration_opens_at')->nullable();
            $table->dateTime('registration_closes_at')->nullable();
            $table->string('status', 20)->default('draft');
            $table->boolean('certificate_enabled')->default(false);
            $table->string('created_by_core_user_id');
            $table->timestamps();
            $table->index(['status', 'starts_at']);
        });

        Schema::create('career_event_topics', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 80)->unique();
            $table->string('label', 100);
            $table->timestamps();
        });

        Schema::create('career_event_topic', function (Blueprint $table) {
            $table->foreignId('career_event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('career_event_topic_id')->constrained()->restrictOnDelete();
            $table->primary(['career_event_id', 'career_event_topic_id']);
        });

        Schema::create('career_event_registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('career_event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('career_profile_id')->constrained()->cascadeOnDelete();
            $table->string('role', 20)->default('participant');
            $table->string('status', 20)->default('registered');
            $table->timestamp('registered_at');
            $table->timestamp('attended_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
            $table->unique(['career_event_id', 'career_profile_id'], 'event_profile_unique');
            $table->index(['career_event_id', 'status']);
            $table->index(['career_profile_id', 'completed_at']);
        });

        Schema::create('career_event_certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('career_event_registration_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('certificate_number', 80)->unique();
            $table->string('verification_code', 64)->unique();
            $table->timestamp('issued_at');
            $table->string('file_path');
            $table->string('file_mime', 100)->default('application/pdf');
            $table->string('issued_by_core_user_id');
            $table->timestamp('revoked_at')->nullable();
            $table->string('revoked_by_core_user_id')->nullable();
            $table->string('revocation_reason')->nullable();
            $table->timestamps();
            $table->index(['issued_at', 'revoked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('career_event_certificates');
        Schema::dropIfExists('career_event_registrations');
        Schema::dropIfExists('career_event_topic');
        Schema::dropIfExists('career_event_topics');
        Schema::dropIfExists('career_events');
    }
};
