<?php

namespace Tests\Feature;

use App\Models\CareerCv;
use App\Models\CareerJob;
use App\Models\CareerJobApplication;
use App\Models\CareerJobInvitation;
use App\Models\Company;
use App\Models\CompanyUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\BuildsCvFixtures;
use Tests\TestCase;

class JobApplicationWorkflowTest extends TestCase
{
    use BuildsCvFixtures, RefreshDatabase;

    public function test_company_and_campus_job_sources_and_lifecycle_are_truthful(): void
    {
        $company = Company::factory()->verified()->create(['display_name' => 'PT Farmasi Teruji']);
        $user = CompanyUser::factory()->for($company)->create();
        $this->withSession(['company_user_id' => $user->id])->post(route('company.jobs.store'), $this->jobPayload())
            ->assertRedirect(route('company.jobs.index'));
        $companyJob = CareerJob::sole();
        $this->assertSame('company_direct', $companyJob->source_type);
        $this->assertSame($company->id, $companyJob->company_id);
        $this->assertSame('draft', $companyJob->status);

        $campus = $this->jobPayload() + ['employer_display_name' => 'Apotek Tanpa Akun SAFA', 'source_type' => 'public_source',
            'source_name' => 'Portal resmi pemberi kerja', 'source_reference' => 'https://example.test/karier/123', 'source_verified' => true];
        $this->withSession(['core_principal' => $this->principal('core-admin', ['admin-karir'])])->post(route('admin.jobs.store'), $campus)->assertRedirect();
        $campusJob = CareerJob::whereNull('company_id')->sole();
        $this->assertSame('campus', $campusJob->created_by_type);
        $this->assertNotNull($campusJob->source_verified_at);
        $this->withSession(['core_principal' => $this->principal('core-admin', ['admin-karir'])])->put(route('admin.jobs.status', $campusJob->public_reference), ['status' => 'published'])->assertRedirect();
        $this->withSession(['core_principal' => $this->principal()])->get(route('jobs.show', $campusJob->public_reference))->assertOk();

        $campusJob->update(['expires_at' => now()->subMinute()]);
        $this->withSession(['core_principal' => $this->principal()])->get(route('jobs.index'))->assertOk();
        $this->assertSame('expired', $campusJob->fresh()->status);
        $this->withSession(['core_principal' => $this->principal()])->get(route('jobs.show', $campusJob->public_reference))->assertNotFound();
    }

    public function test_internal_application_captures_immutable_cv_snapshot(): void
    {
        $profile = $this->profile();
        $this->withSession(['core_principal' => $this->principal()])->post(route('cv.store'), $this->cvPayload($profile))->assertRedirect();
        $cv = CareerCv::sole();
        $job = CareerJob::factory()->create();
        $this->withSession(['core_principal' => $this->principal()])->post(route('jobs.apply', $job->public_reference), ['cv_id' => $cv->id, 'cover_letter' => 'Siap berkontribusi.'])->assertRedirect();
        $application = CareerJobApplication::sole();
        $snapshot = $application->cv_snapshot;
        $checksum = $application->snapshot_checksum;

        $profile->update(['professional_name' => 'Nama Setelah Melamar']);
        $cv->update(['name' => 'CV Setelah Melamar']);
        $this->assertSame($snapshot, $application->fresh()->cv_snapshot);
        $this->assertSame($checksum, $application->fresh()->snapshot_checksum);
        $this->expectException(\LogicException::class);
        $application->update(['snapshot_checksum' => str_repeat('0', 64)]);
    }

    public function test_external_open_is_not_submission_and_self_report_is_explicit(): void
    {
        $profile = $this->profile();
        $job = CareerJob::factory()->create(['application_method' => 'external_url', 'external_apply_url' => 'https://example.test/apply']);
        $session = ['core_principal' => $this->principal()];
        $this->withSession($session)->post(route('jobs.external', $job->public_reference))->assertRedirect();
        $this->assertDatabaseCount('career_job_external_actions', 1);
        $this->assertDatabaseCount('career_job_applications', 0);
        $this->withSession($session)->post(route('jobs.self-report', $job->public_reference))->assertRedirect();
        $this->assertDatabaseHas('career_job_applications', ['career_profile_id' => $profile->id, 'method' => 'external_url', 'status' => 'self_reported']);
    }

    public function test_invitation_is_job_specific_and_acceptance_does_not_submit_application(): void
    {
        $profile = $this->profile();
        $profile->update(['discoverable_by_verified_companies' => true]);
        $company = Company::factory()->verified()->create();
        $user = CompanyUser::factory()->for($company)->create();
        $job = CareerJob::factory()->for($company)->create(['employer_display_name' => $company->display_name]);
        $this->withSession(['company_user_id' => $user->id])->post(route('company.jobs.invite', [$job->public_reference, $profile->talent_reference]), ['message' => 'Profil Anda relevan.'])->assertRedirect();
        $invitation = CareerJobInvitation::sole();
        $this->withSession(['core_principal' => $this->principal()])->put(route('jobs.invitations.respond', $invitation->public_reference), ['decision' => 'accepted'])->assertRedirect();
        $this->assertSame('accepted', $invitation->fresh()->status);
        $this->assertDatabaseCount('career_job_applications', 0);
        $this->assertSame($job->id, $invitation->career_job_id);
    }

    public function test_cross_company_applicant_access_is_not_found_and_draft_is_hidden(): void
    {
        $first = Company::factory()->verified()->create();
        $second = Company::factory()->verified()->create();
        $firstUser = CompanyUser::factory()->for($first)->create();
        $secondUser = CompanyUser::factory()->for($second)->create();
        $job = CareerJob::factory()->for($first)->create();
        $profile = $this->profile();
        $application = $job->applications()->create(['career_profile_id' => $profile->id, 'method' => 'internal', 'status' => 'submitted', 'submitted_at' => now()]);
        $this->withSession(['company_user_id' => $secondUser->id])->get(route('company.jobs.applicants', $job->public_reference))->assertNotFound();
        $this->withSession(['company_user_id' => $secondUser->id])->put(route('company.jobs.applicants.update', [$job->public_reference, $application->public_reference]), ['status' => 'under_review'])->assertNotFound();
        $this->withSession(['company_user_id' => $firstUser->id])->get(route('company.jobs.applicants', $job->public_reference))->assertOk();

        $draft = CareerJob::factory()->create(['status' => 'draft']);
        $this->withSession(['core_principal' => $this->principal()])->get(route('jobs.show', $draft->public_reference))->assertNotFound();
    }

    private function jobPayload(): array
    {
        return ['employer_display_name' => 'Browser value ignored', 'title' => 'Quality Assurance Pharmacist',
            'employment_type' => 'full_time', 'work_mode' => 'onsite', 'city' => 'Karawang',
            'description' => 'Menjaga mutu proses dan dokumentasi farmasi.', 'requirements' => 'Lulusan Farmasi.',
            'application_method' => 'internal', 'expires_at' => now()->addMonth()->toDateString(), 'tags' => 'CPOB, QA/QC, ISO, Halal'];
    }
}
