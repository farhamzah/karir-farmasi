<?php

namespace Tests\Feature;

use App\Cv\CareerCvProjection;
use App\Cv\CvDocxExporter;
use App\Cv\CvPublisher;
use App\Cv\CvShareLinks;
use App\Models\CareerCv;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Support\BuildsCvFixtures;
use Tests\TestCase;
use ZipArchive;

class CvVisibilityAndLinkRenderingTest extends TestCase
{
    use BuildsCvFixtures, RefreshDatabase;

    public function test_visibility_applies_to_preview_publish_public_and_docx(): void
    {
        $profile = $this->profile();
        $profile->update(['linkedin_url' => 'https://linkedin.example/alya', 'portfolio_url' => 'https://portfolio.example/alya']);
        $profile->certifications()->create(['title' => 'Sertifikat Hidden', 'issuer' => 'Lembaga Sintetis', 'credential_url' => 'https://certificate.example/hidden', 'sort_order' => 0]);
        $payload = $this->cvPayload($profile, 'cv-02');
        $payload['field_visibility'] = ['photo' => true, 'city' => true, 'email' => true, 'whatsapp' => false, 'linkedin_url' => true, 'portfolio_url' => false];
        $skillSection = collect($payload['sections'])->search(fn (array $section): bool => $section['key'] === 'skills');
        $certificateSection = collect($payload['sections'])->search(fn (array $section): bool => $section['key'] === 'certifications');
        $payload['sections'][$skillSection]['items'][0]['enabled'] = false;
        $payload['sections'][$certificateSection]['enabled'] = false;

        $session = ['core_principal' => $this->principal()];
        $this->withSession($session)->post(route('cv.store'), $payload)->assertRedirect();
        $cv = CareerCv::sole();
        $snapshot = app(CareerCvProjection::class)->preview($cv);

        $this->assertNull($snapshot['whatsapp']);
        $this->assertNull($snapshot['portfolio_url']);
        $this->assertSame('https://linkedin.example/alya', $snapshot['linkedin_url']);
        $this->assertNotContains('certifications', collect($snapshot['sections'])->pluck('key'));
        $this->assertSame(['Komunikasi pasien'], collect(collect($snapshot['sections'])->firstWhere('key', 'skills')['items'])->pluck('name')->all());
        $this->withSession($session)->get(route('cv.preview', $cv))->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('cv.whatsapp', null)->where('cv.portfolio_url', null));

        $revision = app(CvPublisher::class)->publish($cv);
        $share = app(CvShareLinks::class)->create($cv, []);
        $this->get(route('public-cv.show', $share->token()))->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('cv.whatsapp', null)->where('cv.sections', $revision->snapshot['sections']));
        $serialized = json_encode($revision->snapshot, JSON_THROW_ON_ERROR);
        foreach (['Farmasi klinis', 'Sertifikat Hidden', '0800000000', 'portfolio.example'] as $hiddenValue) {
            $this->assertStringNotContainsString($hiddenValue, $serialized);
        }

        $docxPath = app(CvDocxExporter::class)->export($revision->snapshot);
        $zip = new ZipArchive;
        $this->assertTrue($zip->open($docxPath) === true);
        $document = $zip->getFromName('word/document.xml');
        $this->assertIsString($document);
        $this->assertStringContainsString('LinkedIn', $document);
        foreach (['https://linkedin.example', 'Farmasi klinis', 'Sertifikat Hidden'] as $hiddenValue) {
            $this->assertStringNotContainsString($hiddenValue, $document);
        }
        $zip->close();
        unlink($docxPath);
    }

    public function test_pdf_html_uses_human_link_labels_instead_of_raw_urls(): void
    {
        $profile = $this->profile();
        $profile->update(['linkedin_url' => 'https://linkedin.example/alya', 'portfolio_url' => 'https://portfolio.example/alya']);
        $profile->projects()->create(['title' => 'Portofolio Edukasi Obat', 'description' => 'Materi penggunaan obat yang aman.', 'project_url' => 'https://project.example/raw', 'sort_order' => 0]);
        $profile->publications()->create(['title' => 'Kajian Farmasi Klinis', 'publication_name' => 'Jurnal Sintetis', 'url' => 'https://scholar.google.example/raw', 'sort_order' => 0]);
        $payload = $this->cvPayload($profile, 'cv-03');
        $payload['field_visibility'] = array_fill_keys(['photo', 'city', 'email', 'whatsapp', 'linkedin_url', 'portfolio_url'], true);
        $this->withSession(['core_principal' => $this->principal()])->post(route('cv.store'), $payload)->assertRedirect();

        $snapshot = app(CareerCvProjection::class)->preview(CareerCv::sole());
        $visibleText = html_entity_decode(strip_tags(view('cv.document', ['cv' => $snapshot, 'photoDataUri' => null])->render()));
        foreach (['LinkedIn', 'Portofolio', 'Proyek', 'Google Scholar'] as $label) {
            $this->assertStringContainsString($label, $visibleText);
        }
        foreach (['https://linkedin.example', 'https://portfolio.example', 'https://project.example', 'https://scholar.google.example'] as $rawUrl) {
            $this->assertStringNotContainsString($rawUrl, $visibleText);
        }
    }
}
