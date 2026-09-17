<?php

namespace Tests\Feature;

use App\Authorization\CareerRoleCapabilities;
use App\Data\CareerActor;
use App\Models\CareerEvent;
use App\Models\CareerProfile;
use App\Services\CareerCertificateIssuer;
use App\Services\CareerEventService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class EventDomainWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_capacity_completion_and_certificate_lifecycle(): void
    {
        Storage::fake('career_private');
        $event = CareerEvent::factory()->create(['capacity' => 1]);
        $event->topics()->create(['slug' => 'farmasi-klinis', 'label' => 'Farmasi Klinis']);
        $profile = CareerProfile::factory()->create(['core_user_id' => 'candidate-a']);
        $service = app(CareerEventService::class);
        $registration = $service->register($event, $profile);
        $this->assertSame($registration->id, $service->register($event, $profile)->id);

        try {
            $service->register($event, CareerProfile::factory()->create(['core_user_id' => 'candidate-b']));
            $this->fail('A full event must reject another registration.');
        } catch (ValidationException) {
            $this->assertDatabaseCount('career_event_registrations', 1);
        }

        $actor = new CareerActor('fixture:actor', 'core-admin', 'Admin Sintetis', null, ['admin-karir'], app(CareerRoleCapabilities::class)->forRoles(['admin-karir']));
        $service->attendance($registration, true, $actor);
        $service->completion($registration->fresh(), true, $actor);
        $certificate = app(CareerCertificateIssuer::class)->issue($registration->fresh(), $actor);
        Storage::disk('career_private')->assertExists($certificate->file_path);
        $this->assertStringStartsWith('%PDF-', Storage::disk('career_private')->get($certificate->file_path));
        $this->assertSame($certificate->id, app(CareerCertificateIssuer::class)->issue($registration->fresh(), $actor)->id);
        $this->assertDatabaseHas('audit_events', ['event_type' => 'event.completion.updated']);
    }
}
