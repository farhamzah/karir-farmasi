<?php

namespace Tests\Feature;

use App\Authorization\CareerAuthorization;
use App\Authorization\CareerRoleCapabilities;
use App\Data\CareerActor;
use App\Models\CareerProfile;
use App\Policies\CareerProfileResourcePolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_routes_require_login_and_candidate_capability(): void
    {
        $this->get(route('profile.index'))->assertRedirect(route('home'));

        foreach (['admin-karir', 'petugas-karir', 'viewer-karir'] as $role) {
            $this->withSession(['core_principal' => $this->principal([$role])])
                ->get(route('profile.index'))->assertForbidden();
            $this->withSession(['core_principal' => $this->principal([$role])])
                ->put(route('profile.update'), ['open_to_work' => false, 'profile_visibility' => 'private'])
                ->assertForbidden();
        }
    }

    public function test_multi_role_candidate_is_allowed_but_other_owners_records_are_not_addressable(): void
    {
        $owner = CareerProfile::factory()->create(['core_user_id' => 'core-owner-a']);
        $skill = $owner->skills()->create(['name' => 'Rahasia Sintetis']);

        $this->withSession([
            'core_principal' => $this->principal(['viewer-karir', 'kandidat-karir'], 'core-owner-b'),
            'career_active_role' => 'kandidat-karir',
        ])
            ->get(route('profile.index'))->assertOk();
        $this->withSession(['core_principal' => $this->principal(['kandidat-karir'], 'core-owner-b')])
            ->post(route('profile.sections.update', ['section' => 'skills', 'record' => $skill->id]), ['name' => 'Spoof'])
            ->assertNotFound();
        $this->assertSame('Rahasia Sintetis', $skill->fresh()->name);
    }

    public function test_policy_positive_and_negative_ownership_matrix(): void
    {
        $resource = CareerProfile::factory()->create(['core_user_id' => 'core-owner-a']);
        $capabilities = app(CareerRoleCapabilities::class)->forRoles(['kandidat-karir']);
        $owner = new CareerActor('subject-a', 'core-owner-a', 'Owner', null, ['kandidat-karir'], $capabilities);
        $other = new CareerActor('subject-b', 'core-owner-b', 'Other', null, ['kandidat-karir'], $capabilities);
        $policy = new CareerProfileResourcePolicy(app(CareerAuthorization::class));

        $this->assertTrue($policy->view($owner, $resource));
        $this->assertTrue($policy->update($owner, $resource));
        $this->assertFalse($policy->view($other, $resource));
        $this->assertFalse($policy->delete($other, $resource));
    }

    /** @param list<string> $roles */
    private function principal(array $roles, string $id = 'core-auth-001'): array
    {
        return ['issuer' => 'https://fixture.invalid', 'subject' => 'fixture:'.$id, 'core_user_id' => $id,
            'display_name' => 'Aktor Sintetis', 'email' => 'actor@fixture.invalid', 'active' => true,
            'app_code' => 'karir-farmasi', 'has_app_access' => true, 'roles' => $roles, 'program_ids' => [],
            'verified_at' => '2026-09-12T00:00:00+07:00', 'synthetic' => true];
    }
}
