<?php

namespace Tests\Feature;

use App\Models\CareerProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AlumniDirectoryPrivacyTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_users_only_see_consenting_alumni_in_nim_order_with_minimum_fields(): void
    {
        CareerProfile::factory()->create([
            'core_user_id' => 'alumni-later', 'alumni_number' => '22.000002',
            'professional_name' => 'Budi Farmasi', 'graduation_year' => 2026,
            'professional_email' => 'private-budi@example.test', 'whatsapp' => '08123456789',
        ]);
        CareerProfile::factory()->create([
            'core_user_id' => 'alumni-first', 'alumni_number' => '21.000001',
            'professional_name' => 'Alya Farmasi', 'graduation_year' => 2025,
        ]);
        CareerProfile::factory()->create([
            'core_user_id' => 'alumni-hidden', 'alumni_number' => '20.000001',
            'professional_name' => 'Alumni Privat', 'visible_in_alumni_directory' => false,
        ]);

        $this->withSession(['core_principal' => $this->principal('kandidat-karir', 'viewer-candidate')])
            ->get(route('alumni.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Alumni/Index')
                ->has('alumni', 2)
                ->where('alumni.0.name', 'Alya Farmasi')
                ->where('alumni.0.nim', '21.000001')
                ->where('alumni.0.graduation_year', 2025)
                ->where('alumni.1.name', 'Budi Farmasi')
                ->missing('alumni.1.professional_email')
                ->missing('alumni.1.whatsapp')
                ->missing('alumni.1.core_user_id'));

        $this->withSession(['core_principal' => $this->principal('admin-karir', 'campus-admin')])
            ->get(route('alumni.index', ['q' => 'Budi', 'graduation_year' => 2026]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Alumni/Index')
                ->where('audience', 'staff')
                ->where('unrestricted', true)
                ->where('total', 3)
                ->has('alumni', 1)
                ->where('alumni.0.name', 'Budi Farmasi')
                ->where('filters.q', 'Budi')
                ->where('filters.graduation_year', 2026)
                ->missing('alumni.0.professional_email')
                ->missing('alumni.0.whatsapp')
                ->missing('alumni.0.core_user_id'));
    }

    public function test_directory_and_photos_require_authorized_access_and_directory_consent(): void
    {
        Storage::fake('career_private');
        Storage::disk('career_private')->put('profile-photos/alumni.jpg', 'image-content');
        $visible = CareerProfile::factory()->create(['photo_path' => 'profile-photos/alumni.jpg']);
        $hidden = CareerProfile::factory()->create([
            'photo_path' => 'profile-photos/alumni.jpg', 'visible_in_alumni_directory' => false,
        ]);

        $this->get(route('alumni.index'))->assertRedirect(route('home'));

        foreach (['kandidat-karir', 'petugas-karir', 'viewer-karir'] as $index => $role) {
            $session = ['core_principal' => $this->principal($role, 'directory-user-'.$index)];
            $this->withSession($session)->get(route('alumni.index'))->assertOk();
            $this->withSession($session)->get(route('alumni.photo', $visible))->assertOk()
                ->assertHeader('Cache-Control', 'no-store, private');
            $this->withSession($session)->get(route('alumni.photo', $hidden))->assertNotFound();
        }

        $adminSession = ['core_principal' => $this->principal('admin-karir', 'directory-admin')];
        $this->withSession($adminSession)->get(route('alumni.index'))->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('unrestricted', true)->has('alumni', 2));
        $this->withSession($adminSession)->get(route('alumni.photo', $hidden))->assertOk();
    }

    private function principal(string $role, string $id): array
    {
        return [
            'issuer' => 'https://fixture.invalid', 'subject' => 'fixture:'.$id, 'core_user_id' => $id,
            'display_name' => 'Aktor Sintetis', 'email' => $id.'@fixture.invalid', 'active' => true,
            'app_code' => 'karir-farmasi', 'has_app_access' => true, 'roles' => [$role],
            'program_ids' => [], 'verified_at' => now()->toAtomString(), 'synthetic' => true,
        ];
    }
}
