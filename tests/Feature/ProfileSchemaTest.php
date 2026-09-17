<?php

namespace Tests\Feature;

use App\Models\CareerProfile;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProfileSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_schema_has_canonical_sections_provenance_and_private_paths(): void
    {
        foreach (['career_profiles', 'career_educations', 'career_experiences', 'career_skills', 'career_certifications', 'career_organizations', 'career_projects', 'career_publications', 'career_languages', 'career_job_preferences'] as $table) {
            $this->assertTrue(Schema::hasTable($table), "Missing table {$table}");
        }

        $this->assertTrue(Schema::hasColumns('career_profiles', ['core_user_id', 'professional_email', 'photo_path', 'profile_visibility']));
        $this->assertTrue(Schema::hasColumns('career_educations', ['source', 'source_reference', 'verified_at']));
        $this->assertTrue(Schema::hasColumn('career_certifications', 'attachment_path'));
        $this->assertTrue(Schema::hasColumn('career_projects', 'attachment_path'));
    }

    public function test_owner_is_unique(): void
    {
        CareerProfile::factory()->create(['core_user_id' => 'core-schema-001']);

        $this->expectException(UniqueConstraintViolationException::class);
        CareerProfile::factory()->create(['core_user_id' => 'core-schema-001']);
    }

    public function test_owner_and_private_paths_are_hidden_from_serialization(): void
    {
        $profile = CareerProfile::factory()->create(['core_user_id' => 'private-owner', 'photo_path' => 'private/photo.jpg']);
        $certification = $profile->certifications()->create(['title' => 'Sintetis', 'issuer' => 'UBP', 'attachment_path' => 'private/cert.pdf']);

        $this->assertArrayNotHasKey('core_user_id', $profile->toArray());
        $this->assertArrayNotHasKey('photo_path', $profile->toArray());
        $this->assertArrayNotHasKey('attachment_path', $certification->toArray());
    }
}
