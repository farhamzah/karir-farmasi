<?php

namespace Tests\Feature;

use App\Models\CareerProfile;
use App\Models\LeadershipAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class LeadershipTalentScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_viewer_without_assignment_receives_safe_guidance_without_directory_results(): void
    {
        $this->withSession(['core_principal' => $this->principal('leader-no-scope')])->get(route('internal.talent.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Talent/Search')
                ->where('audience', 'internal')
                ->where('accessUnavailable', true)
                ->has('results', 0));
    }

    public function test_kaprodi_assignment_limits_results_to_its_program(): void
    {
        LeadershipAssignment::factory()->create(['actor_core_user_id' => 'leader-001', 'scope_reference' => 'S1 Farmasi', 'scope_label' => 'Scope: S1 Farmasi']);
        $farmasi = $this->profile('Farmasi UBP', 'S1 Farmasi');
        $this->profile('Profesi UBP', 'Profesi Apoteker');

        $this->withSession(['core_principal' => $this->principal('leader-001')])->get(route('internal.talent.index'))
            ->assertInertia(fn (Assert $page) => $page->component('Talent/Search')->where('audience', 'internal')
                ->where('scope', 'Scope: S1 Farmasi')->has('results', 1)->where('results.0.professional_name', 'Farmasi UBP'));
        $this->withSession(['core_principal' => $this->principal('leader-001')])->get(route('internal.talent.show', $farmasi->talent_reference))->assertOk();
    }

    public function test_assignment_does_not_allow_candidate_edit(): void
    {
        LeadershipAssignment::factory()->create(['actor_core_user_id' => 'leader-001']);
        $profile = $this->profile('Kandidat', 'S1 Farmasi');

        $this->withSession(['core_principal' => $this->principal('leader-001')])->put(route('profile.update'), [
            'professional_name' => 'Diubah', 'open_to_work' => true, 'profile_visibility' => 'private',
        ])->assertForbidden();
        $this->assertSame('Kandidat', $profile->fresh()->professional_name);
    }

    public function test_dekan_faculty_assignment_can_cover_explicit_local_program_list_only(): void
    {
        LeadershipAssignment::factory()->create(['actor_core_user_id' => 'dekan-001', 'scope_type' => 'faculty',
            'scope_reference' => 'fakultas-farmasi', 'scope_label' => 'Scope: Fakultas Farmasi',
            'program_references' => ['S1 Farmasi', 'Profesi Apoteker'], 'role_label' => 'dekan']);
        $this->profile('Sarjana', 'S1 Farmasi');
        $this->profile('Apoteker', 'Profesi Apoteker');
        $this->profile('Di luar fakultas', 'S1 Informatika');

        $this->withSession(['core_principal' => $this->principal('dekan-001')])->get(route('internal.talent.index'))
            ->assertInertia(fn (Assert $page) => $page->where('scope', 'Scope: Fakultas Farmasi')->has('results', 2));
    }

    public function test_internal_scope_honors_separate_internal_consent(): void
    {
        LeadershipAssignment::factory()->create(['actor_core_user_id' => 'leader-001']);
        $profile = $this->profile('Eksternal Saja', 'S1 Farmasi', false);

        $this->withSession(['core_principal' => $this->principal('leader-001')])->get(route('internal.talent.index'))
            ->assertInertia(fn (Assert $page) => $page->has('results', 0));
        $this->withSession(['core_principal' => $this->principal('leader-001')])->get(route('internal.talent.show', $profile->talent_reference))->assertNotFound();
    }

    private function profile(string $name, string $program, bool $internal = true): CareerProfile
    {
        $profile = CareerProfile::factory()->create(['professional_name' => $name, 'discoverable_by_verified_companies' => true, 'discoverable_by_internal_leadership' => $internal]);
        $profile->educations()->create(['institution_name' => 'Universitas Buana Perjuangan Karawang', 'program_name' => $program, 'degree' => 'S1']);

        return $profile->fresh();
    }

    private function principal(string $id): array
    {
        return ['issuer' => 'https://fixture.invalid', 'subject' => 'fixture:'.$id, 'core_user_id' => $id, 'display_name' => 'Pimpinan Sintetis',
            'email' => null, 'active' => true, 'app_code' => 'karir-farmasi', 'has_app_access' => true, 'roles' => ['viewer-karir'],
            'program_ids' => ['farmasi-ubp'], 'verified_at' => '2026-09-12T00:00:00+07:00', 'synthetic' => true];
    }
}
