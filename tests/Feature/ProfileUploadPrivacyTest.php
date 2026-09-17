<?php

namespace Tests\Feature;

use App\Models\CareerProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileUploadPrivacyTest extends TestCase
{
    use RefreshDatabase;

    public function test_photo_is_stored_on_private_disk_and_owner_can_view_it(): void
    {
        Storage::fake('career_private');
        $session = ['core_principal' => $this->principal('core-photo-owner')];

        $this->withSession($session)->post(route('profile.photo.store'), [
            'photo' => UploadedFile::fake()->image('avatar.jpg', 240, 240),
        ])->assertRedirect();

        $profile = CareerProfile::sole();
        Storage::disk('career_private')->assertExists($profile->photo_path);
        $this->assertStringStartsWith('profiles/'.$profile->id.'/photo/', $profile->photo_path);
        $this->withSession($session)->get(route('profile.photo.show'))->assertOk();
        $this->withSession(['core_principal' => $this->principal('core-other')])->get(route('profile.photo.show'))->assertNotFound();
    }

    public function test_invalid_photo_is_rejected_and_does_not_create_profile(): void
    {
        Storage::fake('career_private');
        $this->withSession(['core_principal' => $this->principal()])->post(route('profile.photo.store'), [
            'photo' => UploadedFile::fake()->create('payload.php', 2, 'text/x-php'),
        ])->assertSessionHasErrors('photo');

        $this->assertDatabaseCount('career_profiles', 0);
    }

    public function test_private_certificate_attachment_requires_record_ownership(): void
    {
        Storage::fake('career_private');
        $session = ['core_principal' => $this->principal('core-cert-owner')];
        $this->withSession($session)->post(route('profile.sections.store', 'certifications'), [
            'title' => 'Sertifikat Sintetis', 'issuer' => 'UBP',
            'attachment' => UploadedFile::fake()->create('certificate.pdf', 20, 'application/pdf'),
        ])->assertRedirect();

        $profile = CareerProfile::with('certifications')->sole();
        $certificate = $profile->certifications->sole();
        Storage::disk('career_private')->assertExists($certificate->attachment_path);
        $url = route('profile.files.show', ['section' => 'certifications', 'record' => $certificate->id]);
        $this->withSession($session)->get($url)->assertDownload();
        $this->withSession(['core_principal' => $this->principal('core-other')])->get($url)->assertNotFound();
    }

    private function principal(string $id = 'core-upload-001'): array
    {
        return ['issuer' => 'https://fixture.invalid', 'subject' => 'fixture:'.$id, 'core_user_id' => $id,
            'display_name' => 'Alumni Sintetis', 'email' => 'upload@fixture.invalid', 'active' => true,
            'app_code' => 'karir-farmasi', 'has_app_access' => true, 'roles' => ['kandidat-karir'],
            'program_ids' => [], 'verified_at' => '2026-09-12T00:00:00+07:00', 'synthetic' => true];
    }
}
