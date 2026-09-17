<?php

namespace Tests\Feature;

use App\Models\CareerEvent;
use App\Models\CareerJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_shell_returns_inertia_response(): void
    {
        CareerJob::factory()->create([
            'title' => 'QA Associate',
            'employer_display_name' => 'PT Farmasi Sehat',
            'status' => 'published',
            'published_at' => now(),
            'expires_at' => now()->addWeek(),
        ]);
        CareerEvent::factory()->create([
            'title' => 'Seminar CPOB untuk Industri Farmasi',
            'status' => 'published',
            'starts_at' => now()->addDays(2),
            'ends_at' => now()->addDays(2)->addHours(3),
        ]);
        $response = $this->get('/');

        $response->assertOk();
        $response->assertDontSee('Anisa Susanti');
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Home')
            ->where('identityStatus', 'unavailable')
            ->where('environmentLabel', 'MODE UJI — DATA SINTETIS')
            ->where('opportunityCounts.jobs', 1)
            ->where('opportunityCounts.events', 1)
            ->where('jobs.0.title', 'QA Associate')
            ->where('events.0.title', 'Seminar CPOB untuk Industri Farmasi')
            ->missing('alumni')
            ->missing('profile'));
    }
}
