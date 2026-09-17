<?php

namespace Tests\Feature;

use App\Models\CareerJob;
use App\Models\CareerProfile;
use App\Models\Company;
use App\Models\CompanyUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Support\BuildsCvFixtures;
use Tests\TestCase;

class JobAuthorizationPrivacyTest extends TestCase
{
    use BuildsCvFixtures, RefreshDatabase;

    public function test_officer_can_enter_campus_job_viewer_cannot_and_duplicate_is_flagged(): void
    {
        $payload = $this->payload();
        $officer = ['core_principal' => $this->principal('operator', ['petugas-karir'])];
        $this->withSession($officer)->post(route('admin.jobs.store'), $payload)->assertRedirect();
        $this->withSession($officer)->post(route('admin.jobs.store'), $payload)->assertRedirect();
        $this->assertTrue(CareerJob::query()->latest('id')->firstOrFail()->possible_duplicate);

        $viewer = ['core_principal' => $this->principal('viewer', ['viewer-karir'])];
        $this->withSession($viewer)->post(route('admin.jobs.store'), $payload)->assertForbidden();
    }

    public function test_candidate_result_does_not_expose_contacts_core_ids_or_private_source_notes(): void
    {
        $job = CareerJob::factory()->create(['external_apply_email' => 'private@employer.test', 'internal_notes' => 'Rahasia operasional']);
        $this->withSession(['core_principal' => $this->principal()])->get(route('jobs.index'))
            ->assertInertia(fn (Assert $page) => $page->component('Jobs/Index')
                ->where('jobs.0.reference', $job->public_reference)
                ->missing('jobs.0.external_apply_email')->missing('jobs.0.internal_notes')
                ->missing('jobs.0.created_by_reference')->missing('jobs.0.company_id'));
    }

    public function test_undiscoverable_candidate_cannot_be_invited_and_invitation_requires_own_open_job(): void
    {
        $profile = CareerProfile::factory()->create(['discoverable_by_verified_companies' => false]);
        $company = Company::factory()->verified()->create();
        $user = CompanyUser::factory()->for($company)->create();
        $ownJob = CareerJob::factory()->for($company)->create();
        $otherJob = CareerJob::factory()->for(Company::factory()->verified())->create();
        $session = ['company_user_id' => $user->id];
        $this->withSession($session)->post(route('company.jobs.invite', [$ownJob->public_reference, $profile->talent_reference]))->assertNotFound();
        $profile->update(['discoverable_by_verified_companies' => true]);
        $this->withSession($session)->post(route('company.jobs.invite', [$otherJob->public_reference, $profile->talent_reference]))->assertNotFound();
        $ownJob->update(['status' => 'closed']);
        $this->withSession($session)->post(route('company.jobs.invite', [$ownJob->public_reference, $profile->talent_reference]))->assertUnprocessable();
    }

    public function test_application_status_history_and_ownership_are_enforced(): void
    {
        $company = Company::factory()->verified()->create();
        $recruiter = CompanyUser::factory()->for($company)->create();
        $job = CareerJob::factory()->for($company)->create();
        $owner = $this->profile();
        $this->profile('other-candidate');
        $application = $job->applications()->create(['career_profile_id' => $owner->id, 'method' => 'internal', 'status' => 'submitted', 'submitted_at' => now()]);

        $this->withSession(['company_user_id' => $recruiter->id])->put(route('company.jobs.applicants.update', [$job->public_reference, $application->public_reference]), ['status' => 'under_review', 'note' => 'Dokumen sesuai'])->assertRedirect();
        $this->assertDatabaseHas('career_application_transitions', ['career_job_application_id' => $application->id, 'from_status' => 'submitted', 'to_status' => 'under_review']);
        $this->withSession(['core_principal' => $this->principal('other-candidate')])->delete(route('jobs.applications.withdraw', $application->public_reference))->assertNotFound();
        $this->withSession(['core_principal' => $this->principal()])->delete(route('jobs.applications.withdraw', $application->public_reference))->assertRedirect();
        $this->assertSame('withdrawn', $application->fresh()->status);
        $this->assertSame($owner->id, $application->fresh()->career_profile_id);
    }

    private function payload(): array
    {
        return ['employer_display_name' => 'Apotek Sehat Karawang', 'title' => 'Apoteker Pendamping', 'employment_type' => 'full_time',
            'work_mode' => 'onsite', 'city' => 'Karawang', 'description' => 'Pelayanan kefarmasian yang aman.',
            'application_method' => 'internal', 'source_type' => 'campus_input', 'source_name' => 'Mitra Career Center',
            'source_reference' => 'https://example.test/jobs/apoteker', 'source_verified' => true, 'tags' => 'Apotek, Pelayanan Farmasi'];
    }
}
