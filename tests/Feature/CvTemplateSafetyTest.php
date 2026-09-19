<?php

namespace Tests\Feature;

use App\Cv\CvTemplateCatalog;
use App\Cv\CvTemplateConfiguration;
use App\Models\CareerCv;
use App\Models\CvTemplateVersion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\Support\BuildsCvFixtures;
use Tests\TestCase;

class CvTemplateSafetyTest extends TestCase
{
    use BuildsCvFixtures, RefreshDatabase;

    public function test_only_allowlisted_template_configuration_is_accepted(): void
    {
        $validator = new CvTemplateConfiguration;
        $valid = CvTemplateVersion::firstOrFail()->configuration;
        $this->assertSame($valid, $validator->validated($valid));
        foreach ([array_merge($valid, ['html' => '<script>alert(1)</script>']), array_merge($valid, ['layout' => 'php:eval'])] as $unsafe) {
            try {
                $validator->validated($unsafe);
                $this->fail('Unsafe configuration was accepted.');
            } catch (InvalidArgumentException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function test_cv_keeps_exact_template_version_binding(): void
    {
        $profile = $this->profile();
        $payload = $this->cvPayload($profile, 'cv-01');
        $this->withSession(['core_principal' => $this->principal()])->post(route('cv.store'), $payload);
        $this->assertSame($payload['template_version_id'], CareerCv::sole()->cv_template_version_id);
        $bound = CvTemplateVersion::findOrFail($payload['template_version_id']);
        CvTemplateVersion::create(['cv_template_id' => $bound->cv_template_id, 'version' => '1.1.0',
            'configuration' => $bound->configuration, 'status' => 'published', 'published_at' => now()]);
        $this->assertSame($bound->id, CareerCv::sole()->fresh()->cv_template_version_id);
        $this->assertCount(6, app(CvTemplateCatalog::class)->published());
    }
}
