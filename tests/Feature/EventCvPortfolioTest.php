<?php

namespace Tests\Feature;

use App\Cv\CareerCvProjection;
use App\Cv\CvDocxExporter;
use App\Cv\CvPublisher;
use App\Cv\CvShareLinks;
use App\Models\CareerCv;
use App\Models\CareerEvent;
use App\Models\CareerEventCertificate;
use App\Models\CareerEventRegistration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\BuildsCvFixtures;
use Tests\TestCase;
use ZipArchive;

class EventCvPortfolioTest extends TestCase
{
    use BuildsCvFixtures, RefreshDatabase;

    public function test_completed_event_and_certificate_are_selectable_and_obey_per_cv_visibility(): void
    {
        $profile = $this->profile();
        $event = CareerEvent::factory()->create(['title' => 'Seminar Farmasi Klinis']);
        $event->topics()->create(['slug' => 'patient-safety', 'label' => 'Patient Safety']);
        $registration = CareerEventRegistration::factory()->create(['career_event_id' => $event->id, 'career_profile_id' => $profile->id,
            'role' => 'speaker', 'attended_at' => now(), 'completed_at' => now(), 'status' => 'completed']);
        CareerEventCertificate::factory()->create(['career_event_registration_id' => $registration->id, 'certificate_number' => 'CERT-SYNTHETIC-001']);
        $payload = $this->cvPayload($profile);
        $this->withSession(['core_principal' => $this->principal()])->post(route('cv.store'), $payload)->assertRedirect();
        $cv = CareerCv::sole();
        $snapshot = app(CareerCvProjection::class)->preview($cv);
        $eventSection = collect($snapshot['sections'])->firstWhere('key', 'events');
        $this->assertSame('Seminar Farmasi Klinis', $eventSection['items'][0]['title']);
        $this->assertSame('Pembicara', $eventSection['items'][0]['role']);
        $this->assertSame('Patient Safety', $eventSection['items'][0]['topics']);
        $this->assertStringNotContainsString('file_path', json_encode($snapshot, JSON_THROW_ON_ERROR));

        $eventIndex = collect($payload['sections'])->search(fn ($section) => $section['key'] === 'events');
        $certificateIndex = collect($payload['sections'])->search(fn ($section) => $section['key'] === 'event_certificates');
        $payload['sections'][$eventIndex]['enabled'] = false;
        $payload['sections'][$certificateIndex]['enabled'] = false;
        $this->withSession(['core_principal' => $this->principal()])->put(route('cv.update', $cv), $payload)->assertRedirect();
        $hidden = json_encode(app(CareerCvProjection::class)->preview($cv->fresh()), JSON_THROW_ON_ERROR);
        $this->assertStringNotContainsString('Seminar Farmasi Klinis', $hidden);
        $this->assertStringNotContainsString('CERT-SYNTHETIC-001', $hidden);

        $revision = app(CvPublisher::class)->publish($cv->fresh());
        $share = app(CvShareLinks::class)->create($cv->fresh(), ['allow_pdf_download' => true]);
        $this->get(route('public-cv.show', $share->token()))->assertOk()
            ->assertDontSee('Seminar Farmasi Klinis')->assertDontSee('CERT-SYNTHETIC-001');
        $printHtml = view('cv.document', ['cv' => $revision->snapshot, 'photoDataUri' => null])->render();
        $this->assertStringNotContainsString('Seminar Farmasi Klinis', $printHtml);
        $docxPath = app(CvDocxExporter::class)->export($revision->snapshot);
        $zip = new ZipArchive;
        $this->assertTrue($zip->open($docxPath) === true);
        $document = $zip->getFromName('word/document.xml');
        $this->assertStringNotContainsString('Seminar Farmasi Klinis', (string) $document);
        $this->assertStringNotContainsString('CERT-SYNTHETIC-001', (string) $document);
        $zip->close();
        unlink($docxPath);
    }
}
