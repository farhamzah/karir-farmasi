<?php

namespace Tests\Feature;

use App\Cv\ChromiumCvPdfRenderer;
use Tests\TestCase;

class CvPdfRendererTest extends TestCase
{
    public function test_first_five_templates_show_experience_without_internal_field_labels_or_added_tagline(): void
    {
        foreach (range(1, 5) as $number) {
            $html = view('cv.document', [
                'cv' => [
                    'professional_name' => 'Alumni Sintetis',
                    'headline' => 'Quality Control',
                    'template' => ['key' => sprintf('cv-%02d', $number)],
                    'sections' => [[
                        'key' => 'experience',
                        'title' => 'Pengalaman',
                        'items' => [[
                            'title' => 'Quality Control',
                            'type' => 'Pengalaman Kerja',
                            'organization' => 'Laboratorium Sintetis',
                            'location' => 'Karawang',
                            'start_date' => 'Apr 2018',
                            'end_date' => 'Feb 2020',
                            'description' => 'Kalibrasi alat ukur dan dokumentasi hasil pengujian.',
                        ]],
                    ]],
                ],
                'photoDataUri' => null,
            ])->render();

            $this->assertStringContainsString('Laboratorium Sintetis · Karawang', $html);
            $this->assertStringContainsString('Apr 2018 – Feb 2020', $html);
            $this->assertStringNotContainsString('>Organisasi<', $html);
            $this->assertStringNotContainsString('>Lokasi<', $html);
            $this->assertStringNotContainsString('Apoteker untuk kualitas hidup yang lebih baik', $html);
        }
    }

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
                ['key' => 'education', 'title' => 'Pendidikan', 'items' => [['program_name' => 'Farmasi', 'degree' => 'S1', 'institution_name' => 'Universitas Sintetis', 'gpa' => '3.78']]],
            ],
        ];
        $html = view('cv.document', ['cv' => $snapshot, 'photoDataUri' => null])->render();
        $this->assertStringContainsString('<!doctype html>', $html);
        $this->assertStringContainsString('Alya Sintetis', $html);
        $this->assertStringContainsString('Universitas Sintetis', $html);
        $this->assertStringContainsString('IPK', $html);
        $this->assertStringContainsString('3.78', $html);

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
