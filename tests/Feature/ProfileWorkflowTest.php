<?php

namespace Tests\Feature;

use App\Models\CareerProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ProfileWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_empty_profile_is_accessible_and_progress_is_guidance_only(): void
    {
        $this->withSession(['core_principal' => $this->principal()])->get(route('profile.index'))
            ->assertOk()->assertInertia(fn (Assert $page) => $page->component('Profile/Overview')
            ->where('progress.percent', 0)->where('progress.is_gate', false)->has('sections', 9));
    }

    public function test_profile_owner_comes_from_current_actor_and_contact_is_separate(): void
    {
        $this->withSession(['core_principal' => $this->principal('core-owner-001')])->put(route('profile.update'), [
            'core_user_id' => 'spoofed-owner', 'professional_name' => 'Alumni Sintetis',
            'professional_email' => 'career@fixture.invalid', 'open_to_work' => true,
            'profile_visibility' => 'private', 'section_visibility' => ['education' => true],
        ])->assertRedirect(route('profile.index'));

        $profile = CareerProfile::sole();
        $this->assertSame('core-owner-001', $profile->core_user_id);
        $this->assertSame('career@fixture.invalid', $profile->professional_email);
        $this->assertTrue($profile->open_to_work);
    }

    public function test_multiple_education_and_all_supported_experience_types_are_saved(): void
    {
        $session = ['core_principal' => $this->principal()];
        foreach (['Farmasi UBP', 'Program Profesi Apoteker'] as $institution) {
            $this->withSession($session)->post(route('profile.sections.store', 'education'), [
                'institution_name' => $institution, 'program_name' => 'Farmasi', 'status' => 'graduated',
            ])->assertRedirect();
        }
        foreach (['work', 'internship', 'pkpa', 'kp', 'volunteer', 'other'] as $type) {
            $this->withSession($session)->post(route('profile.sections.store', 'experience'), [
                'type' => $type, 'organization' => 'Organisasi Sintetis', 'title' => 'Peran Sintetis',
            ])->assertRedirect();
        }

        $profile = CareerProfile::with(['educations', 'experiences'])->sole();
        $this->assertCount(2, $profile->educations);
        $this->assertTrue($profile->educations->every(fn ($item) => $item->source === 'user_declared' && $item->verified_at === null));
        $this->assertEqualsCanonicalizing(['work', 'internship', 'pkpa', 'kp', 'volunteer', 'other'], $profile->experiences->pluck('type')->all());
    }

    public function test_education_gpa_is_optional_and_must_use_a_valid_four_point_scale(): void
    {
        $session = ['core_principal' => $this->principal()];
        $education = ['institution_name' => 'Universitas Sintetis', 'program_name' => 'Farmasi'];

        $this->withSession($session)->post(route('profile.sections.store', 'education'), [...$education, 'gpa' => '3.78'])
            ->assertRedirect();
        $record = CareerProfile::sole()->educations()->sole();
        $this->assertSame('3.78', $record->gpa);

        foreach (['4.01', '3.789'] as $invalid) {
            $this->withSession($session)->post(route('profile.sections.store', 'education'), [...$education, 'gpa' => $invalid])
                ->assertSessionHasErrors('gpa');
        }

        $this->withSession($session)->post(route('profile.sections.update', ['education', $record->id]), [...$education, 'gpa' => ''])
            ->assertRedirect();
        $this->assertNull($record->fresh()->gpa);
    }

    public function test_remaining_sections_and_confirmation_are_persisted(): void
    {
        $session = ['core_principal' => $this->principal()];
        $payloads = [
            'skills' => ['name' => 'Farmasi klinis'], 'certifications' => ['title' => 'Pelatihan Sintetis', 'issuer' => 'UBP'],
            'organizations' => ['organization' => 'Organisasi Sintetis', 'role' => 'Anggota'], 'projects' => ['title' => 'Proyek Sintetis'],
            'publications' => ['title' => 'Publikasi Sintetis'], 'languages' => ['language' => 'Indonesia', 'proficiency' => 'Aktif'],
            'preferences' => ['target_roles' => 'Apoteker, Peneliti', 'preferred_locations' => 'Karawang'],
        ];
        foreach ($payloads as $section => $payload) {
            $this->withSession($session)->post(route('profile.sections.store', $section), $payload)->assertRedirect();
        }
        $this->withSession($session)->post(route('profile.confirm'))->assertRedirect();

        $profile = CareerProfile::with(['skills', 'certifications', 'organizations', 'projects', 'publications', 'languages', 'jobPreference'])->sole();
        $this->assertNotNull($profile->last_confirmed_at);
        $this->assertSame(['Apoteker', 'Peneliti'], $profile->jobPreference->target_roles);
        $this->assertSame('Farmasi klinis', $profile->skills->sole()->name);
    }

    public function test_candidate_can_update_and_delete_each_repeatable_professional_section(): void
    {
        $session = ['core_principal' => $this->principal()];
        $cases = [
            'skills' => [['name' => 'Awal'], ['name' => 'Diperbarui'], 'skills'],
            'certifications' => [['title' => 'Awal', 'issuer' => 'UBP'], ['title' => 'Diperbarui', 'issuer' => 'UBP'], 'certifications'],
            'organizations' => [['organization' => 'Awal', 'role' => 'Anggota'], ['organization' => 'Diperbarui', 'role' => 'Anggota'], 'organizations'],
            'projects' => [['title' => 'Awal'], ['title' => 'Diperbarui'], 'projects'],
            'publications' => [['title' => 'Awal'], ['title' => 'Diperbarui'], 'publications'],
            'languages' => [['language' => 'Awal'], ['language' => 'Diperbarui'], 'languages'],
        ];

        foreach ($cases as $section => [$create, $update, $relation]) {
            $this->withSession($session)->post(route('profile.sections.store', $section), $create)->assertRedirect();
            $profile = CareerProfile::firstOrFail();
            $record = $profile->{$relation}()->latest('id')->firstOrFail();
            $this->withSession($session)->post(route('profile.sections.update', [$section, $record->id]), $update)->assertRedirect();
            $this->withSession($session)->delete(route('profile.sections.destroy', [$section, $record->id]))->assertRedirect();
            $this->assertDatabaseMissing($record->getTable(), ['id' => $record->id]);
        }
    }

    public function test_core_verified_education_keeps_provenance_read_only(): void
    {
        $profile = CareerProfile::factory()->create(['core_user_id' => 'core-profile-001']);
        $education = $profile->educations()->create([
            'institution_name' => 'Farmasi UBP', 'program_name' => 'Farmasi',
            'source' => 'core_verified', 'source_reference' => 'synthetic-ref', 'verified_at' => now(),
        ]);
        $session = ['core_principal' => $this->principal()];

        $this->withSession($session)->post(route('profile.sections.update', ['education', $education->id]), [
            'institution_name' => 'Spoof', 'program_name' => 'Spoof',
        ])->assertForbidden();
        $this->withSession($session)->delete(route('profile.sections.destroy', ['education', $education->id]))
            ->assertForbidden();
        $this->assertSame('core_verified', $education->fresh()->source);
    }

    private function principal(string $id = 'core-profile-001'): array
    {
        return ['issuer' => 'https://fixture.invalid', 'subject' => 'fixture:'.$id, 'core_user_id' => $id,
            'display_name' => 'Alumni Sintetis', 'email' => 'login@fixture.invalid', 'active' => true,
            'app_code' => 'karir-farmasi', 'has_app_access' => true, 'roles' => ['kandidat-karir'],
            'program_ids' => [], 'verified_at' => '2026-09-12T00:00:00+07:00', 'synthetic' => true];
    }
}
