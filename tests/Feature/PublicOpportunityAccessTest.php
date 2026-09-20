<?php

namespace Tests\Feature;

use App\Models\CareerEvent;
use App\Models\CareerJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Support\BuildsCvFixtures;
use Tests\TestCase;

class PublicOpportunityAccessTest extends TestCase
{
    use BuildsCvFixtures, RefreshDatabase;

    public function test_jobs_are_public_and_newest_publication_is_first(): void
    {
        $older = CareerJob::factory()->create([
            'title' => 'Lowongan Lama',
            'published_at' => now()->subDays(3),
        ]);
        $newer = CareerJob::factory()->create([
            'title' => 'Lowongan Terbaru',
            'published_at' => now()->subDay(),
        ]);

        $this->get(route('jobs.index'))->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Jobs/Index')
            ->where('canInteract', false)
            ->where('jobs.0.reference', $newer->public_reference)
            ->where('jobs.0.title', 'Lowongan Terbaru')
            ->has('jobs.0.published_at')
            ->where('jobs.1.reference', $older->public_reference));

        $this->get(route('jobs.show', $newer->public_reference))->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Jobs/Show')
            ->where('canInteract', false)
            ->where('job.title', 'Lowongan Terbaru')
            ->has('job.description')
            ->has('job.published_at')
            ->where('job.external_apply_url', null)
            ->where('job.external_apply_email', null));
    }

    public function test_event_information_is_public_but_registration_requires_login(): void
    {
        $event = CareerEvent::factory()->create(['title' => 'Seminar Farmasi Publik']);

        $this->get(route('events.index'))->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Event/Index')
            ->where('canRegister', false)
            ->where('events.0.title', 'Seminar Farmasi Publik'));

        $this->get(route('events.show', $event))->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Event/Show')
            ->where('canRegister', false)
            ->where('event.title', 'Seminar Farmasi Publik')
            ->has('event.description'));

        $this->post(route('events.register', $event))->assertRedirect(route('home'));
    }

    public function test_candidate_keeps_private_opportunity_actions(): void
    {
        $job = CareerJob::factory()->create();
        $event = CareerEvent::factory()->create();
        $session = ['core_principal' => $this->principal()];

        $this->withSession($session)->get(route('jobs.show', $job->public_reference))
            ->assertInertia(fn (Assert $page) => $page->where('canInteract', true));
        $this->withSession($session)->get(route('events.show', $event))
            ->assertInertia(fn (Assert $page) => $page->where('canRegister', true));
    }
}
