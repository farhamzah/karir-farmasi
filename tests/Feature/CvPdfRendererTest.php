<?php

namespace Tests\Feature;

use App\Cv\ChromiumCvPdfRenderer;
use Tests\TestCase;

class CvPdfRendererTest extends TestCase
{
    public function test_cv_document_is_rendered_as_a_real_pdf(): void
    {
        if (! config('cv_exports.chromium_path')) {
            $this->markTestSkipped('Chromium is required to render a real PDF.');
        }

        $snapshot = [
            'professional_name' => 'Alya Sintetis',
            'headline' => 'Apoteker',
            'template' => ['key' => 'cv-05'],
            'sections' => [
                ['key' => 'summary', 'title' => 'Ringkasan Profesional', 'items' => [['description' => 'Apoteker dengan pengalaman layanan farmasi.']]],
                ['key' => 'education', 'title' => 'Pendidikan', 'items' => [['program_name' => 'Farmasi', 'degree' => 'S1', 'institution_name' => 'Universitas Sintetis']]],
            ],
        ];
        $html = view('cv.document', ['cv' => $snapshot, 'photoDataUri' => null])->render();
        $this->assertStringContainsString('<!doctype html>', $html);
        $this->assertStringContainsString('Alya Sintetis', $html);
        $this->assertStringContainsString('Universitas Sintetis', $html);

        $path = app(ChromiumCvPdfRenderer::class)->render($snapshot);

        try {
            $this->assertFileExists($path);
            $this->assertGreaterThan(5000, filesize($path));
            $this->assertStringStartsWith('%PDF-', file_get_contents($path));
        } finally {
            unlink($path);
        }
    }
}
