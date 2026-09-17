<?php

namespace Tests\Feature;

use App\Models\CareerEvent;
use App\Models\CareerProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_candidate_admin_officer_and_viewer_boundaries(): void
    {
        $event = CareerEvent::factory()->create();
        CareerProfile::factory()->create(['core_user_id' => 'candidate']);
        $this->withSession(['core_principal' => $this->principal('kandidat-karir', 'candidate')])->get(route('events.index'))->assertOk();
        $this->withSession(['core_principal' => $this->principal('kandidat-karir', 'candidate')])->post(route('events.register', $event))->assertRedirect();
        $this->withSession(['core_principal' => $this->principal('kandidat-karir', 'candidate')])->get(route('admin.events.index'))->assertForbidden();
        $this->withSession(['core_principal' => $this->principal('admin-karir', 'admin')])->get(route('admin.events.index'))->assertOk();
        $this->withSession(['core_principal' => $this->principal('petugas-karir', 'officer')])->get(route('admin.events.participants', $event))->assertOk();
        $this->withSession(['core_principal' => $this->principal('petugas-karir', 'officer')])->get(route('admin.events.create'))->assertForbidden();
        $this->withSession(['core_principal' => $this->principal('viewer-karir', 'viewer')])->get(route('admin.events.index'))->assertOk()->assertDontSee('candidate@fixture.invalid');
        $this->withSession(['core_principal' => $this->principal('viewer-karir', 'viewer')])->get(route('admin.events.participants', $event))->assertForbidden();
    }

    public function test_admin_can_create_publish_and_close_event_with_structured_topics(): void
    {
        $session = ['core_principal' => $this->principal('admin-karir', 'admin')];
        $payload = ['title' => 'Workshop Keselamatan Pasien', 'event_type' => 'workshop', 'organizer' => 'Farmasi UBP',
            'description' => 'Kegiatan sintetis untuk pengujian.', 'starts_at' => now()->addWeek()->toDateTimeString(),
            'ends_at' => now()->addWeek()->addHours(3)->toDateTimeString(), 'location_type' => 'onsite',
            'location_text' => 'Kampus UBP', 'capacity' => 50, 'registration_opens_at' => now()->toDateTimeString(),
            'registration_closes_at' => now()->addDays(5)->toDateTimeString(), 'certificate_enabled' => true,
            'topics' => ['Patient Safety', 'Farmasi Klinis']];
        $this->withSession($session)->post(route('admin.events.store'), $payload)->assertRedirect(route('admin.events.index'));
        $event = CareerEvent::sole();
        $this->assertSame(['Farmasi Klinis', 'Patient Safety'], $event->topics()->orderBy('label')->pluck('label')->all());
        $this->withSession($session)->put(route('admin.events.status', $event), ['status' => 'published'])->assertRedirect();
        $this->assertSame('published', $event->fresh()->status);
        $this->withSession($session)->put(route('admin.events.status', $event), ['status' => 'closed'])->assertRedirect();
        $this->assertSame('closed', $event->fresh()->status);
    }

    private function principal(string $role, string $id): array
    {
        return ['issuer' => 'https://fixture.invalid', 'subject' => 'fixture:'.$id, 'core_user_id' => $id, 'display_name' => 'Aktor Sintetis',
            'email' => $id.'@fixture.invalid', 'active' => true, 'app_code' => 'karir-farmasi', 'has_app_access' => true, 'roles' => [$role],
            'program_ids' => [], 'verified_at' => now()->toAtomString(), 'synthetic' => true];
    }
}
