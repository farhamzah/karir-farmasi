<?php

namespace Tests\Feature;

use App\Models\CareerJob;
use App\Models\CareerJobImportBatch;
use App\Models\CareerNotification;
use App\Models\Company;
use App\Models\CompanyUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Support\BuildsCvFixtures;
use Tests\TestCase;

class Final01OperationalTest extends TestCase
{
    use BuildsCvFixtures, RefreshDatabase;

    public function test_application_documents_are_private_and_company_scoped(): void
    {
        Storage::fake('career_private');
        $profile = $this->profile();
        $company = Company::factory()->verified()->create();
        $owner = CompanyUser::factory()->for($company)->create();
        $other = CompanyUser::factory()->for(Company::factory()->verified())->create();
        $job = CareerJob::factory()->for($company)->create();
        $application = $job->applications()->create(['career_profile_id' => $profile->id, 'method' => 'internal', 'status' => 'submitted', 'submitted_at' => now()]);

        $this->withSession(['core_principal' => $this->principal()])->post(route('jobs.applications.documents.store', $application->public_reference), [
            'type' => 'supporting_document',
            'document' => UploadedFile::fake()->create('portfolio.pdf', 30, 'application/pdf'),
        ])->assertRedirect();

        $document = $application->documents()->sole();
        Storage::disk('career_private')->assertExists($document->path);
        $this->withSession(['company_user_id' => $owner->id])->get(route('company.applications.documents.show', [$application->public_reference, $document]))->assertOk();
        $this->withSession(['company_user_id' => $other->id])->get(route('company.applications.documents.show', [$application->public_reference, $document]))->assertNotFound();
    }

    public function test_key_job_workflows_create_readable_notifications(): void
    {
        $profile = $this->profile();
        $profile->update(['discoverable_by_verified_companies' => true]);
        $company = Company::factory()->verified()->create();
        $user = CompanyUser::factory()->for($company)->create();
        $job = CareerJob::factory()->for($company)->create();

        $this->withSession(['company_user_id' => $user->id])->post(route('company.jobs.invite', [$job->public_reference, $profile->talent_reference]))->assertRedirect();
        $this->assertDatabaseHas('career_notifications', ['recipient_reference' => $profile->core_user_id, 'type' => 'job.invitation.created']);

        $application = $job->applications()->create(['career_profile_id' => $profile->id, 'method' => 'internal', 'status' => 'submitted', 'submitted_at' => now()]);
        $this->withSession(['company_user_id' => $user->id])->put(route('company.jobs.applicants.update', [$job->public_reference, $application->public_reference]), ['status' => 'under_review'])->assertRedirect();
        $notice = CareerNotification::query()->where('type', 'application.status_changed')->sole();
        $this->withSession(['core_principal' => $this->principal()])->put(route('notifications.read', $notice->public_reference))->assertRedirect();
        $this->assertNotNull($notice->fresh()->read_at);
    }

    public function test_expiry_command_is_idempotent(): void
    {
        $expired = CareerJob::factory()->create(['status' => 'published', 'expires_at' => now()->subMinute()]);
        $active = CareerJob::factory()->create(['status' => 'published', 'expires_at' => now()->addDay()]);
        $this->artisan('career:expire-jobs')->assertSuccessful()->expectsOutput('Expired 1 career job(s).');
        $this->artisan('career:expire-jobs')->assertSuccessful()->expectsOutput('Expired 0 career job(s).');
        $this->assertSame('expired', $expired->fresh()->status);
        $this->assertSame('published', $active->fresh()->status);
    }

    public function test_csv_import_previews_validation_and_never_auto_publishes(): void
    {
        CareerJob::factory()->create(['employer_display_name' => 'PT Aman', 'title' => 'QA Pharmacist']);
        $csv = "employer_display_name,title,employment_type,work_mode,description,application_method,source_name,city,tags\n";
        $csv .= "PT Aman,QA Pharmacist,full_time,onsite,Menjaga mutu,internal,HR resmi,Karawang,CPOB\n";
        $csv .= "PT Baru,Regulatory Officer,salah,hybrid,Registrasi produk,internal,Kampus,Karawang,Regulatory\n";
        $session = ['core_principal' => $this->principal('admin-final', ['admin-karir'])];

        $this->withSession($session)->post(route('admin.jobs.import.preview'), ['file' => UploadedFile::fake()->createWithContent('jobs.csv', $csv)])->assertRedirect();
        $batch = CareerJobImportBatch::query()->sole();
        $this->assertSame(1, $batch->valid_count);
        $this->assertSame(1, $batch->invalid_count);
        $this->assertSame(1, $batch->duplicate_count);

        $this->withSession($session)->post(route('admin.jobs.import.store', $batch->public_reference))->assertRedirect(route('admin.jobs.index'));
        $this->assertDatabaseHas('career_jobs', ['employer_display_name' => 'PT Aman', 'title' => 'QA Pharmacist', 'status' => 'review', 'possible_duplicate' => true]);
        $this->assertDatabaseMissing('career_jobs', ['status' => 'published', 'created_by_type' => 'campus_import']);
    }
}
