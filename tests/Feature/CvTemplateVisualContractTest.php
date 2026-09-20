<?php

namespace Tests\Feature;

use App\Cv\CvTemplateCatalog;
use App\Models\CareerCv;
use App\Models\CvTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Support\BuildsCvFixtures;
use Tests\TestCase;

class CvTemplateVisualContractTest extends TestCase
{
    use BuildsCvFixtures, RefreshDatabase;

    public function test_nine_templates_have_distinct_safe_visual_identities(): void
    {
        $templates = app(CvTemplateCatalog::class)->frontend();
        $this->assertSame(['cv-01', 'cv-02', 'cv-03', 'cv-04', 'cv-05', 'cv-06', 'cv-07', 'cv-08', 'cv-09'], array_column($templates, 'key'));
        $this->assertCount(9, collect($templates)->pluck('configuration')->map(fn ($config) => json_encode($config))->unique());
        foreach ($templates as $template) {
            $this->assertCount(8, $template['configuration']);
        }
    }

    public function test_switching_across_all_templates_and_back_preserves_cv_configuration(): void
    {
        $profile = $this->profile();
        $session = ['core_principal' => $this->principal()];
        $payload = $this->cvPayload($profile, 'cv-01');
        $payload['sections'][3]['items'][0]['enabled'] = false;
        $payload['sections'][3]['display_title'] = 'Keahlian Pilihan';
        $this->withSession($session)->post(route('cv.store'), $payload)->assertRedirect();
        $cv = CareerCv::sole();

        foreach (['cv-02', 'cv-03', 'cv-04', 'cv-05', 'cv-06', 'cv-07', 'cv-08', 'cv-09', 'cv-01'] as $key) {
            $payload['template_version_id'] = $this->cvPayload($profile, $key)['template_version_id'];
            $this->withSession($session)->put(route('cv.update', $cv), $payload)->assertRedirect();
        }

        $this->assertSame('Keahlian Pilihan', $cv->sectionPreferences()->where('section_key', 'skills')->value('display_title'));
        $this->assertFalse((bool) $cv->itemPreferences()->where('section_key', 'skills')->where('sort_order', 0)->value('enabled'));
        $this->assertDatabaseCount('career_profiles', 1);
    }

    public function test_all_templates_project_long_unicode_dense_and_empty_content_safely(): void
    {
        $profile = $this->profile();
        $profile->update([
            'professional_name' => str_repeat('Alya Maharani Ž Ž ', 10),
            'professional_email' => str_repeat('alumni.', 20).'@fixture.invalid',
            'whatsapp' => '+62'.str_repeat('1234567890', 2), 'photo_path' => null,
        ]);
        for ($index = 0; $index < 12; $index++) {
            $profile->experiences()->create([
                'type' => 'work', 'organization' => 'Organisasi Sintetis '.str_repeat('Panjang ', 8),
                'title' => "Pengalaman {$index} — Δ Farmasi", 'description' => str_repeat('Uraian aman dan terukur. ', 12), 'sort_order' => $index,
            ]);
        }
        $session = ['core_principal' => $this->principal()];
        foreach (['cv-01', 'cv-02', 'cv-03', 'cv-04', 'cv-05', 'cv-06', 'cv-07', 'cv-08', 'cv-09'] as $key) {
            $payload = $this->cvPayload($profile, $key, strtoupper($key));
            $payload['sections'][0]['enabled'] = false;
            $this->withSession($session)->post(route('cv.store'), $payload)->assertRedirect();
        }

        foreach (CareerCv::all() as $cv) {
            $this->withSession($session)->get(route('cv.preview', $cv))->assertOk()->assertInertia(fn (Assert $page) => $page
                ->component('Cv/Preview')->where('cv.has_photo', false)
                ->where('cv.professional_name', $profile->professional_name)
                ->where('cv.sections', fn ($sections) => ! collect($sections)->pluck('key')->contains('summary')));
        }
    }

    public function test_admin_preview_uses_synthetic_fixture_without_candidate_identifiers(): void
    {
        $template = CvTemplate::where('key', 'cv-05')->sole();
        $response = $this->withSession(['core_principal' => $this->principal('admin', ['admin-karir'])])
            ->get(route('admin.cv-templates.preview', $template));
        $response->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Admin/CvTemplates/Preview')->where('fixture.professional_name', 'Alya Nūr Pramesti, S.Farm.')
            ->missing('fixture.core_user_id')->missing('fixture.credential'));
    }
}
