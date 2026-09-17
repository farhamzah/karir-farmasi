<?php

namespace Tests\Feature;

use App\Models\CareerJob;
use App\Models\CareerProfile;
use App\Models\Company;
use App\Models\CompanyUser;
use App\Models\LeadershipAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Support\BuildsCvFixtures;
use Tests\TestCase;

class ReportingPrivacyTest extends TestCase
{
    use BuildsCvFixtures, RefreshDatabase;

    public function test_feedback_requires_verified_company_and_owned_application_relationship(): void
    {
        $profile = $this->profile();
        $company = Company::factory()->verified()->create();
        $owner = CompanyUser::factory()->for($company)->create();
        $other = CompanyUser::factory()->for(Company::factory()->verified())->create();
        $job = CareerJob::factory()->for($company)->create();
        $application = $job->applications()->create(['career_profile_id' => $profile->id, 'method' => 'internal', 'status' => 'interview', 'submitted_at' => now()]);
        $payload = ['relationship' => 'interviewed', 'ratings' => ['professionalism' => 5, 'communication' => 4, 'technical' => 4]];

        $this->withSession(['company_user_id' => $other->id])->post(route('company.applications.feedback.store', $application->public_reference), $payload)->assertNotFound();
        $this->withSession(['company_user_id' => $owner->id])->post(route('company.applications.feedback.store', $application->public_reference), $payload)->assertRedirect();
        $this->assertDatabaseHas('employer_feedback', ['company_id' => $company->id, 'career_profile_id' => $profile->id]);
    }

    public function test_leership_dashboard_is_scoped_and_contains_no_raw_private_fields(): void
    {
        LeadershipAssignment::factory()->create(['actor_core_user_id' => 'leader-01', 'scope_reference' => 'S1 Farmasi']);
        $inside = CareerProfile::factory()->create(['professional_name' => 'Nama Privat Dalam Scope']);
        $inside->educations()->create(['institution_name' => 'UBP', 'program_name' => 'S1 Farmasi']);
        $outside = CareerProfile::factory()->create(['professional_name' => 'Nama Privat Luar Scope']);
        $outside->educations()->create(['institution_name' => 'UBP', 'program_name' => 'Profesi Apoteker']);

        $session = ['core_principal' => $this->principal('leader-01', ['viewer-karir'])];
        $this->withSession($session)->get(route('staff.overview'))->assertInertia(fn (Assert $page) => $page
            ->component('Staff/Overview')->where('summary.candidate_profiles', 1)
            ->missing('summary.raw_tracer')->missing('summary.cv_snapshot')->missing('summary.phone'));
        $csv = $this->withSession($session)->get(route('staff.exports.download', 'csv'))->assertOk();
        $csv->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringNotContainsString('Nama Privat', $csv->streamedContent());
        $xlsx = $this->withSession($session)->get(route('staff.exports.download', 'xlsx'))->assertOk();
        $this->assertStringStartsWith('PK', (string) file_get_contents($xlsx->getFile()->getPathname()));
    }
}
