<?php

namespace Tests\Feature;

use App\Models\CareerEvent;
use App\Models\CareerEventRegistration;
use App\Models\CareerEventTopic;
use App\Models\CareerProfile;
use App\Models\Company;
use App\Models\CompanyShortlist;
use App\Models\CompanyUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TalentSearchPrivacyTest extends TestCase
{
    use RefreshDatabase;

    public function test_external_and_internal_discoverability_are_independent_and_spoofed_owner_is_ignored(): void
    {
        $session = ['core_principal' => $this->candidatePrincipal('owner-001')];
        $this->withSession($session)->put(route('profile.discoverability.update'), [
            'core_user_id' => 'spoofed', 'discoverable_by_verified_companies' => true,
            'discoverable_by_internal_leadership' => false,
        ])->assertRedirect();

        $profile = CareerProfile::sole();
        $this->assertSame('owner-001', $profile->core_user_id);
        $this->assertTrue($profile->discoverable_by_verified_companies);
        $this->assertFalse($profile->discoverable_by_internal_leadership);
    }

    public function test_verified_company_finds_structured_alias_and_event_tags_without_private_fields(): void
    {
        $profile = $this->indexedProfile();
        $companyUser = CompanyUser::factory()->for(Company::factory()->verified())->create();

        $this->withSession(['company_user_id' => $companyUser->id])->get(route('company.talent.index', ['q' => 'GMP']))
            ->assertInertia(fn (Assert $page) => $page->component('Talent/Search')->has('results', 1)
                ->where('results.0.professional_name', 'Anisa Susanti')->where('results.0.matched.0', 'CPOB')
                ->missing('results.0.email')->missing('results.0.phone')->missing('results.0.core_user_id')
                ->missing('results.0.attachment_path'));
        $this->withSession(['company_user_id' => $companyUser->id])->get(route('company.talent.index', ['q' => 'Halal']))
            ->assertInertia(fn (Assert $page) => $page->has('results', 1));
        $this->withSession(['company_user_id' => $companyUser->id])->get(route('company.talent.show', $profile->talent_reference))
            ->assertInertia(fn (Assert $page) => $page->component('Talent/Profile')->missing('profile.professional_email')
                ->missing('profile.whatsapp')->missing('profile.core_user_id')->missing('profile.certifications.0.attachment_path'));
        $this->assertDatabaseHas('talent_access_audits', ['action' => 'talent.profile.view', 'company_id' => $companyUser->company_id]);
    }

    public function test_filters_and_revoke_remove_candidate_immediately(): void
    {
        $profile = $this->indexedProfile();
        $user = CompanyUser::factory()->for(Company::factory()->verified())->create();
        $matchingFilters = [
            'city' => 'Karawang', 'open_to_work' => 1, 'education_program' => 'S1 Farmasi',
            'education_level' => 'Sarjana', 'graduation_year' => 2026, 'skill' => 'CPOB',
            'certification' => 'ISO 9001 dan Sistem Jaminan Produk Halal', 'event_topic' => 'Halal',
            'experience_type' => 'work', 'sector' => 'Rumah Sakit dan Apotek Sehat',
            'preferred_location' => 'Jakarta', 'availability_date' => '2026-10-01',
            'willing_to_relocate' => 1,
        ];
        foreach ($matchingFilters as $filter => $value) {
            $this->withSession(['company_user_id' => $user->id])->get(route('company.talent.index', [$filter => $value]))
                ->assertInertia(fn (Assert $page) => $page->has('results', 1), "Expected match for filter [$filter].");
        }
        $this->withSession(['company_user_id' => $user->id])->get(route('company.talent.index', ['city' => 'Bandung']))
            ->assertInertia(fn (Assert $page) => $page->has('results', 0));

        $profile->update(['discoverable_by_verified_companies' => false]);
        $this->withSession(['company_user_id' => $user->id])->get(route('company.talent.index'))
            ->assertInertia(fn (Assert $page) => $page->has('results', 0));
        $this->withSession(['company_user_id' => $user->id])->get(route('company.talent.show', $profile->talent_reference))->assertNotFound();
    }

    public function test_required_pharmacy_terms_are_found_from_declared_structured_sources(): void
    {
        $this->indexedProfile();
        $user = CompanyUser::factory()->for(Company::factory()->verified())->create();

        foreach (['CPOB', 'RS', 'ISO', 'Halal', 'QA/QC', 'Regulatory', 'Produksi', 'Apotek', 'PBF'] as $term) {
            $this->withSession(['company_user_id' => $user->id])->get(route('company.talent.index', ['q' => $term]))
                ->assertInertia(fn (Assert $page) => $page->has('results', 1), "Expected structured match for [$term].");
        }
    }

    public function test_shortlists_are_isolated_by_company(): void
    {
        $profile = $this->indexedProfile();
        $first = CompanyUser::factory()->for(Company::factory()->verified())->create();
        $second = CompanyUser::factory()->for(Company::factory()->verified())->create();
        $this->withSession(['company_user_id' => $first->id])->post(route('company.talent.shortlist', $profile->talent_reference))->assertRedirect();

        $this->withSession(['company_user_id' => $second->id])->get(route('company.talent.index'))
            ->assertInertia(fn (Assert $page) => $page->where('results.0.shortlisted', false));
        $this->assertSame(1, CompanyShortlist::count());
        $this->assertSame($first->company_id, CompanyShortlist::sole()->company_id);
    }

    public function test_talent_search_requires_authentication_and_is_rate_limited(): void
    {
        $this->get(route('company.talent.index'))->assertRedirect(route('company.login'));
        $user = CompanyUser::factory()->for(Company::factory()->verified())->create();

        for ($attempt = 1; $attempt <= 39; $attempt++) {
            $this->withSession(['company_user_id' => $user->id])->get(route('company.talent.index'))->assertOk();
        }
        $this->withSession(['company_user_id' => $user->id])->get(route('company.talent.index'))->assertTooManyRequests();
    }

    private function indexedProfile(): CareerProfile
    {
        $profile = CareerProfile::factory()->create(['professional_name' => 'Anisa Susanti', 'professional_email' => 'private@example.test',
            'whatsapp' => '08120000000', 'city' => 'Karawang', 'open_to_work' => true,
            'discoverable_by_verified_companies' => true, 'discoverable_by_internal_leadership' => true, 'last_confirmed_at' => now()]);
        $profile->educations()->create(['institution_name' => 'Universitas Buana Perjuangan Karawang', 'program_name' => 'S1 Farmasi', 'degree' => 'Sarjana Farmasi', 'end_year' => 2026]);
        $profile->skills()->create(['name' => 'CPOB', 'category' => 'Quality Assurance']);
        $profile->skills()->create(['name' => 'QA/QC', 'category' => 'Produksi']);
        $profile->certifications()->create(['title' => 'ISO 9001 dan Sistem Jaminan Produk Halal', 'issuer' => 'Lembaga Sintetis', 'attachment_path' => 'private/secret.pdf']);
        $profile->experiences()->create(['type' => 'work', 'organization' => 'Rumah Sakit dan Apotek Sehat', 'title' => 'Regulatory PBF', 'description' => 'Mendukung produksi obat.']);
        $profile->jobPreference()->create([
            'target_roles' => ['Quality Assurance'], 'employment_types' => ['full-time'],
            'preferred_locations' => ['Jakarta'], 'willing_to_relocate' => true,
            'availability_date' => '2026-10-01',
        ]);
        $event = CareerEvent::factory()->create(['title' => 'Pelatihan Regulatory dan PBF']);
        $topic = CareerEventTopic::factory()->create(['slug' => 'halal', 'label' => 'Halal']);
        $event->topics()->attach($topic);
        CareerEventRegistration::factory()->for($event, 'event')->for($profile, 'profile')->create(['status' => 'completed', 'completed_at' => now()]);

        return $profile->fresh();
    }

    private function candidatePrincipal(string $id): array
    {
        return ['issuer' => 'https://fixture.invalid', 'subject' => 'fixture:'.$id, 'core_user_id' => $id, 'display_name' => 'Alumni Sintetis',
            'email' => null, 'active' => true, 'app_code' => 'karir-farmasi', 'has_app_access' => true, 'roles' => ['kandidat-karir'],
            'program_ids' => ['farmasi-ubp'], 'verified_at' => '2026-09-12T00:00:00+07:00', 'synthetic' => true];
    }
}
