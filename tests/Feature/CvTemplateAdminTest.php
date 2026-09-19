<?php

namespace Tests\Feature;

use App\Cv\CvTemplateCatalog;
use App\Cv\CvTemplateConfiguration;
use App\Models\CareerCv;
use App\Models\CvTemplate;
use App\Models\CvTemplateVersion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Support\BuildsCvFixtures;
use Tests\TestCase;

class CvTemplateAdminTest extends TestCase
{
    use BuildsCvFixtures, RefreshDatabase;

    public function test_only_admin_karir_can_access_template_management(): void
    {
        $this->get(route('admin.cv-templates.index'))->assertRedirect(route('home'));
        foreach (['kandidat-karir', 'petugas-karir', 'viewer-karir'] as $role) {
            $this->withSession(['core_principal' => $this->principal('actor-'.$role, [$role])])
                ->get(route('admin.cv-templates.index'))->assertForbidden();
        }
        $this->withSession(['core_principal' => $this->principal('admin', ['admin-karir'])])
            ->get(route('admin.cv-templates.index'))->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Admin/CvTemplates/Index')->has('templates', 6));
    }

    public function test_admin_creates_sixth_safe_variation_without_code_and_publishes_it(): void
    {
        $session = ['core_principal' => $this->principal('admin', ['admin-karir'])];
        $this->withSession($session)->post(route('admin.cv-templates.store'), [
            'key' => 'cv-06-sintetis', 'name' => 'Komunitas Farmasi',
            'description' => 'Variasi sintetis untuk pengujian kontrak.', 'base_template_key' => 'cv-03',
        ])->assertRedirect();
        $template = CvTemplate::where('key', 'cv-06-sintetis')->sole();
        $this->assertSame('draft', $template->versions()->sole()->status);

        $configuration = [
            'layout' => 'split', 'photo' => 'oval', 'typography' => 'modern', 'spacing' => 'comfortable',
            'header_style' => 'hero', 'section_style' => 'accent', 'accent' => 'soft', 'page_padding' => 'compact',
        ];
        $this->withSession($session)->put(route('admin.cv-templates.update', $template), [
            'name' => 'Komunitas Farmasi', 'description' => 'Diperbarui hanya melalui token allowlist.',
            'configuration' => $configuration,
        ])->assertRedirect();
        $this->withSession($session)->post(route('admin.cv-templates.publish', $template))->assertRedirect();
        $this->assertEquals($configuration, $template->versions()->where('status', 'published')->sole()->configuration);
        $this->assertCount(7, app(CvTemplateCatalog::class)->published());
    }

    public function test_admin_can_duplicate_publish_retire_and_reactivate_template(): void
    {
        $session = ['core_principal' => $this->principal('admin', ['admin-karir'])];
        $source = CvTemplate::where('key', 'cv-02')->sole();
        $this->withSession($session)->post(route('admin.cv-templates.duplicate', $source), ['key' => 'cv-02-copy'])->assertRedirect();
        $copy = CvTemplate::where('key', 'cv-02-copy')->sole();
        $this->withSession($session)->post(route('admin.cv-templates.publish', $copy))->assertRedirect();
        $this->assertTrue($copy->fresh()->active);
        $this->withSession($session)->post(route('admin.cv-templates.retire', $copy))->assertRedirect();
        $this->assertFalse($copy->fresh()->active);
        $this->withSession($session)->post(route('admin.cv-templates.reactivate', $copy))->assertRedirect();
        $this->assertTrue($copy->fresh()->active);
    }

    public function test_published_version_is_immutable_and_retired_binding_remains_renderable(): void
    {
        $profile = $this->profile();
        $candidate = ['core_principal' => $this->principal()];
        $payload = $this->cvPayload($profile, 'cv-01');
        $this->withSession($candidate)->post(route('cv.store'), $payload)->assertRedirect();
        $cv = CareerCv::sole();
        $boundVersionId = $cv->cv_template_version_id;

        $template = CvTemplate::where('key', 'cv-01')->sole();
        $admin = ['core_principal' => $this->principal('admin', ['admin-karir'])];
        $configuration = app(CvTemplateConfiguration::class)->normalized($cv->templateVersion->configuration);
        $configuration['spacing'] = 'compact';
        $this->withSession($admin)->put(route('admin.cv-templates.update', $template), [
            'name' => $template->name, 'description' => $template->description, 'configuration' => $configuration,
        ])->assertRedirect();
        $this->withSession($admin)->post(route('admin.cv-templates.publish', $template))->assertRedirect();
        $this->assertSame($boundVersionId, $cv->fresh()->cv_template_version_id);
        $this->withSession($admin)->post(route('admin.cv-templates.retire', $template))->assertRedirect();
        $this->withSession($candidate)->get(route('cv.preview', $cv))->assertOk();

        $newPayload = $payload;
        $newPayload['name'] = 'CV baru ditolak';
        $this->withSession($candidate)->post(route('cv.store'), $newPayload)->assertSessionHasErrors('template_version_id');
        $payload['name'] = 'CV lama tetap dapat disimpan';
        $this->withSession($candidate)->put(route('cv.update', $cv), $payload)->assertRedirect();
    }

    public function test_raw_code_and_unexpected_configuration_keys_are_rejected(): void
    {
        $template = CvTemplate::where('key', 'cv-03')->sole();
        $valid = app(CvTemplateConfiguration::class)->normalized($this->currentVersion($template)->configuration);
        $session = ['core_principal' => $this->principal('admin', ['admin-karir'])];

        foreach ([array_merge($valid, ['html' => '<script>alert(1)</script>']), array_replace($valid, ['layout' => 'url(https://evil.invalid)'])] as $configuration) {
            $this->withSession($session)->put(route('admin.cv-templates.update', $template), [
                'name' => $template->name, 'description' => $template->description, 'configuration' => $configuration,
            ])->assertSessionHasErrors();
        }
        $this->assertFalse($template->versions()->where('status', 'draft')->exists());
    }

    private function currentVersion(CvTemplate $template): CvTemplateVersion
    {
        return $template->versions()->where('status', 'published')->latest('published_at')->latest('id')->firstOrFail();
    }
}
