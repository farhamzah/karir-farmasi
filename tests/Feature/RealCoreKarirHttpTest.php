<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RealCoreKarirHttpTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_minimal_alumni_round_trip_over_real_local_http(): void
    {
        if (env('SKR002B_REAL_HTTP') !== '1') {
            $this->markTestSkipped('Run only against the isolated SKR-002B Core HTTP service.');
        }

        $password = (string) env('SKR002B_CANDIDATE_PASSWORD');
        $runId = preg_replace('/[^A-Z0-9]/', '', strtoupper((string) env('SKR002B_RUN_ID', 'RUN')));
        $referenceRoute = route('registration-status.index');
        $registration = [
            'student_number' => 'SYN-'.$runId,
            'full_name' => 'Alumni HTTP Sintetis',
            'claimed_program' => 'S1 Farmasi',
            'graduation_year' => 2025,
            'personal_email' => strtolower('alumni.'.$runId.'@example.invalid'),
            'whatsapp' => '08000000202',
            'password' => $password,
            'password_confirmation' => $password,
        ];

        $register = $this->post(route('register.store'), $registration)->assertSessionHasNoErrors();
        $reference = basename((string) parse_url($register->headers->get('Location'), PHP_URL_PATH));
        $this->assertNotSame('', $reference);
        $this->get(route('registration-status.show', $reference))
            ->assertOk()->assertInertia(fn ($page) => $page->where('registration.status', 'pending'));

        $this->from($referenceRoute)->post(route('internal-session.store'), [
            'identifier' => $registration['student_number'], 'password' => $password,
        ])->assertSessionHasErrors('identifier');

        $this->post(route('internal-session.store'), [
            'identifier' => env('SKR002B_ADMIN_IDENTIFIER'),
            'password' => env('SKR002B_ADMIN_PASSWORD'),
        ])->assertRedirect(route('admin.registrations.index'));
        $this->get(route('admin.registrations.index'))->assertOk();
        $this->get(route('admin.registrations.show', $reference))->assertOk();
        $this->post(route('admin.registrations.approve', $reference), [
            'approver_core_user_id' => 'spoofed-browser-id',
        ])->assertSessionHasNoErrors()->assertRedirect(route('admin.registrations.show', $reference));

        $this->delete(route('internal-session.destroy'))->assertRedirect(route('home'));
        $this->post(route('internal-session.store'), [
            'identifier' => $registration['student_number'], 'password' => $password,
        ])->assertRedirect(route('dashboard'));
        $this->get(route('dashboard'))->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Dashboard')
                ->where('principal.display_name', 'Alumni Http Sintetis'));
    }
}
