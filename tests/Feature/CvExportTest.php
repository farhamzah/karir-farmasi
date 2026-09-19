<?php

namespace Tests\Feature;

use App\Contracts\CvPdfRenderer;
use App\Cv\CareerCvProjection;
use App\Cv\CvDocxExporter;
use App\Cv\CvPublisher;
use App\Cv\CvShareLinks;
use App\Models\CareerCv;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\BuildsCvFixtures;
use Tests\TestCase;
use ZipArchive;

class CvExportTest extends TestCase
{
    use BuildsCvFixtures, RefreshDatabase;

    public function test_private_pdf_uses_browser_renderer_for_all_six_templates_and_is_own_own_only(): void
    {
        $renderer = new class implements CvPdfRenderer
        {
            /** @var list<array<string, mixed>> */
            public array $snapshots = [];

            public function render(array $snapshot, ?string $photoDataUri = null): string
            {
                $this->snapshots[] = $snapshot;
                $path = tempnam(sys_get_temp_dir(), 'cv-pdf-');
                file_put_contents($path, "%PDF-1.4\n% synthetic selectable text\n%%EOF");

                return $path;
            }
        };
        $this->app->instance(CvPdfRenderer::class, $renderer);
        $profile = $this->profile();
        $session = ['core_principal' => $this->principal()];
        $profile->skills()->create(['name' => 'Unicode 日本語 العربية', 'sort_order' => 8]);
        foreach (['cv-01', 'cv-02', 'cv-03', 'cv-04', 'cv-05', 'cv-06'] as $key) {
            $this->withSession($session)->post(route('cv.store'), $this->cvPayload($profile, $key, 'CV '.$key));
            $cv = CareerCv::latest('id')->firstOrFail();
            $this->withSession($session)->get(route('cv.pdf', $cv))->assertOk()->assertDownload('cv-'.$key.'.pdf');
        }

        $this->assertSame(['cv-01', 'cv-02', 'cv-03', 'cv-04', 'cv-05', 'cv-06'], collect($renderer->snapshots)->pluck('template.key')->all());
        $cv = CareerCv::firstOrFail();
        $this->withSession(['core_principal' => $this->principal('other')])->get(route('cv.pdf', $cv))->assertNotFound();
    }

    public function test_docx_is_valid_editable_ooxml_with_selected_unicode_data_and_no_internal_fields(): void
    {
        $profile = $this->profile();
        $profile->update(['professional_name' => 'Alya Nūr — 日本語']);
        $this->withSession(['core_principal' => $this->principal()])->post(route('cv.store'), $this->cvPayload($profile));
        $cv = CareerCv::sole();
        $snapshot = app(CareerCvProjection::class)->preview($cv);
        $png = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=';
        $path = app(CvDocxExporter::class)->export($snapshot, $png);

        $zip = new ZipArchive;
        $this->assertTrue($zip->open($path) === true);
        foreach (['[Content_Types].xml', '_rels/.rels', 'word/document.xml', 'word/styles.xml', 'word/_rels/document.xml.rels'] as $part) {
            $this->assertNotFalse($zip->locateName($part));
        }
        $document = $zip->getFromName('word/document.xml');
        $this->assertIsString($document);
        $this->assertStringContainsString('Alya Nūr — 日本語', $document);
        $this->assertStringContainsString('Ringkasan khusus yang relevan.', $document);
        $this->assertStringNotContainsString('core-cv-owner', $document);
        $this->assertStringNotContainsString('login@fixture.invalid', $document);
        $this->assertNotFalse($zip->locateName('word/media/profile.png'));
        $relationships = $zip->getFromName('word/_rels/document.xml.rels');
        $this->assertIsString($relationships);
        $this->assertStringContainsString('relationships/image', $relationships);
        $this->assertStringContainsString('r:embed="rId2"', $document);
        $zip->close();
        unlink($path);
    }

    public function test_public_pdf_obeys_owner_permission_without_revealing_link_state(): void
    {
        $renderer = new class implements CvPdfRenderer
        {
            public function render(array $snapshot, ?string $photoDataUri = null): string
            {
                $path = tempnam(sys_get_temp_dir(), 'cv-pdf-');
                file_put_contents($path, "%PDF-1.4\n%%EOF");

                return $path;
            }
        };
        $this->app->instance(CvPdfRenderer::class, $renderer);
        $profile = $this->profile();
        $this->withSession(['core_principal' => $this->principal()])->post(route('cv.store'), $this->cvPayload($profile));
        $cv = CareerCv::sole();
        app(CvPublisher::class)->publish($cv);
        $blocked = app(CvShareLinks::class)->create($cv, ['allow_pdf_download' => false]);
        $this->get(route('public-cv.pdf', $blocked->token()))->assertNotFound();

        $allowed = app(CvShareLinks::class)->create($cv, ['allow_pdf_download' => true]);
        $this->get(route('public-cv.pdf', $allowed->token()))->assertOk()->assertDownload('cv-alya-nur-sintetis.pdf');

        $allowed->update(['active' => false]);
        $this->get(route('public-cv.pdf', $allowed->token()))->assertNotFound();
    }
}
