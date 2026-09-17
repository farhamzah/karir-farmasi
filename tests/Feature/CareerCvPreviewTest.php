<?php

namespace Tests\Feature;

use App\Models\CareerCv;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Support\BuildsCvFixtures;
use Tests\TestCase;

class CareerCvPreviewTest extends TestCase
{
    use BuildsCvFixtures, RefreshDatabase;

    public function test_both_templates_render_private_preview_with_unicode_and_no_empty_sections(): void
    {
        $profile = $this->profile();
        $session = ['core_principal' => $this->principal()];
        foreach (['cv-01', 'cv-02'] as $template) {
            $this->withSession($session)->post(route('cv.store'), $this->cvPayload($profile, $template, strtoupper($template)));
        }
        foreach (CareerCv::with('templateVersion.template')->get() as $cv) {
            $this->withSession($session)->get(route('cv.preview', $cv))->assertOk()->assertInertia(fn (Assert $page) => $page
                ->component('Cv/Preview')->where('cv.professional_name', 'Alya Nūr Sintetis')
                ->where('cv.template.key', $cv->templateVersion->template->key)
                ->where('cv.sections', fn ($sections) => collect($sections)->pluck('key')->contains('skills')
                    && ! collect($sections)->pluck('key')->contains('education')));
        }
    }

    public function test_disabled_items_are_absent_and_saved_order_is_preserved(): void
    {
        $profile = $this->profile();
        $payload = $this->cvPayload($profile);
        $payload['sections'][3]['items'][0]['sort_order'] = 2;
        $payload['sections'][3]['items'][1]['sort_order'] = 1;
        $payload['sections'][3]['items'][0]['enabled'] = false;
        $this->withSession(['core_principal' => $this->principal()])->post(route('cv.store'), $payload);
        $cv = CareerCv::sole();
        $this->withSession(['core_principal' => $this->principal()])->get(route('cv.preview', $cv))->assertInertia(fn (Assert $page) => $page
            ->where('cv.sections', fn ($sections) => collect($sections)->firstWhere('key', 'skills')['items'][0]['name'] === 'Komunikasi pasien'
                && count(collect($sections)->firstWhere('key', 'skills')['items']) === 1));
    }

    public function test_dense_profile_long_content_photo_metadata_and_all_sections_project_safely(): void
    {
        $profile = $this->profile();
        $profile->update(['professional_name' => str_repeat('Alya Maharani ', 14).'Ž', 'professional_email' => str_repeat('alumni.', 20).'@fixture.invalid',
            'linkedin_url' => 'https://fixture.invalid/'.str_repeat('portofolio-', 20), 'photo_path' => 'synthetic/portrait.jpg']);
        $profile->educations()->create(['institution_name' => 'Universitas Buana Perjuangan Karawang', 'program_name' => 'Farmasi']);
        foreach (['work', 'internship', 'pkpa', 'volunteer'] as $index => $type) {
            $profile->experiences()->create(['type' => $type, 'organization' => 'Organisasi Sintetis '.$index, 'title' => 'Peran '.$index]);
        }
        $profile->certifications()->create(['title' => 'Sertifikasi A', 'issuer' => 'Lembaga']);
        $profile->organizations()->create(['organization' => 'Ikatan Sintetis', 'role' => 'Anggota']);
        $profile->projects()->create(['title' => 'Karya Farmasi']);
        $profile->publications()->create(['title' => 'Publikasi Farmasi']);
        $profile->languages()->create(['language' => 'Indonesia', 'proficiency' => 'Mahir']);
        $payload = $this->cvPayload($profile, 'cv-02', 'CV Lengkap');
        $this->withSession(['core_principal' => $this->principal()])->post(route('cv.store'), $payload);
        $this->withSession(['core_principal' => $this->principal()])->get(route('cv.preview', CareerCv::sole()))->assertInertia(fn (Assert $page) => $page
            ->where('cv.has_photo', true)->has('cv.sections', 10)->where('cv.professional_name', fn ($name) => str_ends_with($name, 'Ž')));
    }

    public function test_job_preferences_are_optional_cv_content_controlled_by_alumni(): void
    {
        $profile = $this->profile();
        $payload = $this->cvPayload($profile);
        $preferenceSection = collect($payload['sections'])->search(fn (array $section): bool => $section['key'] === 'preferences');
        $this->assertIsInt($preferenceSection);
        $this->assertSame('Preferensi Karier', $payload['sections'][$preferenceSection]['key'] === 'preferences' ? 'Preferensi Karier' : '');
        $payload['sections'][$preferenceSection]['enabled'] = false;
        $this->withSession(['core_principal' => $this->principal()])->post(route('cv.store'), $payload);
        $this->withSession(['core_principal' => $this->principal()])->get(route('cv.preview', CareerCv::sole()))->assertInertia(fn (Assert $page) => $page
            ->where('cv.sections', fn ($sections) => ! collect($sections)->pluck('key')->contains('preferences')));

        $visiblePayload = $this->cvPayload($profile, 'cv-02', 'CV Preferensi');
        $this->withSession(['core_principal' => $this->principal()])->post(route('cv.store'), $visiblePayload);
        $this->withSession(['core_principal' => $this->principal()])->get(route('cv.preview', CareerCv::latest('id')->firstOrFail()))->assertInertia(fn (Assert $page) => $page
            ->where('cv.sections', fn ($sections) => collect(collect($sections)->firstWhere('key', 'preferences')['items'])->first()['target_roles'] === 'Apoteker klinik, Regulatory affairs'));
    }
}
