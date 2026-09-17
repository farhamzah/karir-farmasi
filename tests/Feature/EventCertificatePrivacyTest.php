<?php

namespace Tests\Feature;

use App\Models\CareerEventCertificate;
use App\Models\CareerEventRegistration;
use App\Models\CareerProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EventCertificatePrivacyTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_owner_can_download_non_revoked_private_certificate(): void
    {
        Storage::fake('career_private');
        $profile = CareerProfile::factory()->create(['core_user_id' => 'owner']);
        $registration = CareerEventRegistration::factory()->create(['career_profile_id' => $profile->id, 'completed_at' => now()]);
        $certificate = CareerEventCertificate::factory()->create(['career_event_registration_id' => $registration->id]);
        Storage::disk('career_private')->put($certificate->file_path, '%PDF-private fixture');
        $response = $this->withSession(['core_principal' => $this->principal('owner')])->get(route('events.certificate', $certificate))->assertOk();
        $this->assertStringContainsString('private', $response->headers->get('cache-control'));
        $this->assertStringContainsString('no-store', $response->headers->get('cache-control'));
        $this->withSession(['core_principal' => $this->principal('other')])->get(route('events.certificate', $certificate))->assertNotFound();
        $certificate->update(['revoked_at' => now(), 'revocation_reason' => 'Fixture revoke']);
        $this->withSession(['core_principal' => $this->principal('owner')])->get(route('events.certificate', $certificate))->assertNotFound();
        $this->assertFalse(file_exists(public_path($certificate->file_path)));
    }

    private function principal(string $id): array
    {
        return ['issuer' => 'https://fixture.invalid', 'subject' => 'fixture:'.$id, 'core_user_id' => $id, 'display_name' => 'Kandidat',
            'email' => null, 'active' => true, 'app_code' => 'karir-farmasi', 'has_app_access' => true, 'roles' => ['kandidat-karir'],
            'program_ids' => [], 'verified_at' => now()->toAtomString(), 'synthetic' => true];
    }
}
