<?php

namespace Tests\Feature;

use App\Models\CareerJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class JobFlyerWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_flyer_first_job_and_public_flyer_waits_for_publish(): void
    {
        Storage::fake('career_private');
        $session = ['core_principal' => $this->adminPrincipal()];

        $this->withSession($session)->post(route('admin.jobs.store'), $this->payload([
            'description' => '',
            'flyer' => UploadedFile::fake()->image('lowongan-qa.jpg', 1000, 1400),
            'flyer_alt_text' => 'Flyer lowongan Quality Assurance',
        ]))->assertRedirect(route('admin.jobs.index'));

        $job = CareerJob::sole();
        $this->assertSame('draft', $job->status);
        $this->assertSame('Informasi lengkap tersedia pada flyer lowongan.', $job->description);
        $this->assertNotNull($job->flyer_path);
        Storage::disk('career_private')->assertExists($job->flyer_path);

        $this->get(route('job-flyers.show', $job->public_reference))->assertNotFound();
        $this->withSession($session)->get(route('admin.jobs.flyer', $job->public_reference))
            ->assertOk()->assertHeader('Cache-Control', 'no-store, private');

        $this->withSession($session)->put(route('admin.jobs.status', $job->public_reference), ['status' => 'published'])
            ->assertRedirect();
        $this->get(route('job-flyers.show', $job->public_reference))
            ->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_admin_must_supply_description_or_flyer(): void
    {
        $this->withSession(['core_principal' => $this->adminPrincipal()])
            ->from(route('admin.jobs.create'))
            ->post(route('admin.jobs.store'), $this->payload(['description' => '']))
            ->assertRedirect(route('admin.jobs.create'))
            ->assertSessionHasErrors(['description', 'flyer']);

        $this->assertDatabaseCount('career_jobs', 0);
    }

    public function test_text_only_job_remains_supported(): void
    {
        $this->withSession(['core_principal' => $this->adminPrincipal()])
            ->post(route('admin.jobs.store'), $this->payload())
            ->assertRedirect(route('admin.jobs.index'));

        $job = CareerJob::sole();
        $this->assertNull($job->flyer_path);
        $this->assertSame('Lowongan ditulis langsung oleh petugas kampus.', $job->description);
    }

    private function payload(array $overrides = []): array
    {
        return array_replace([
            'employer_display_name' => 'PT Farmasi Karawang',
            'title' => 'Quality Assurance Pharmacist',
            'employment_type' => 'full_time',
            'work_mode' => 'onsite',
            'city' => 'Karawang',
            'description' => 'Lowongan ditulis langsung oleh petugas kampus.',
            'application_method' => 'internal',
            'expires_at' => now()->addMonth()->toDateString(),
            'source_type' => 'campus_input',
            'source_name' => 'Career Center Farmasi UBP',
            'tags' => 'CPOB, QA/QC',
        ], $overrides);
    }

    private function adminPrincipal(): array
    {
        return [
            'issuer' => 'https://fixture.invalid',
            'subject' => 'fixture:core-admin',
            'core_user_id' => 'core-admin',
            'display_name' => 'Admin Karir',
            'email' => 'admin@example.test',
            'active' => true,
            'app_code' => 'karir-farmasi',
            'has_app_access' => true,
            'roles' => ['admin-karir'],
            'program_ids' => [],
            'verified_at' => '2026-09-18T00:00:00+07:00',
            'synthetic' => true,
        ];
    }
}
