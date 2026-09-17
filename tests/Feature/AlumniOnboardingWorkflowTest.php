<?php

namespace Tests\Feature;

use App\Contracts\CoreAlumniGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Fakes\FakeCoreAlumniGateway;
use Tests\TestCase;

class AlumniOnboardingWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private FakeCoreAlumniGateway $gateway;

    protected function setUp(): void
    {
        parent::setUp();
        $this->gateway = new FakeCoreAlumniGateway;
        $this->app->instance(CoreAlumniGateway::class, $this->gateway);
    }

    public function test_alumni_registers_with_minimum_data_and_is_sent_to_pending_status(): void
    {
        $response = $this->post(route('register.store'), $this->registrationData());

        $response->assertRedirect(route('registration-status.show', 'KARIR-SYN-001'));
        $this->assertSame('SYN-001', $this->gateway->registered['student_number']);
        $this->assertArrayNotHasKey('password_confirmation', $this->gateway->registered);
    }

    public function test_registration_requires_no_otp_or_complete_core_profile(): void
    {
        $data = $this->registrationData();
        unset($data['graduation_year']);
        $this->post(route('register.store'), $data)->assertSessionHasNoErrors();

        $this->assertArrayNotHasKey('otp', $this->gateway->registered);
        $this->assertArrayNotHasKey('core_profile', $this->gateway->registered);
    }

    public function test_approved_existing_account_status_is_rendered_for_password_guidance(): void
    {
        $this->gateway->statusResponse = [
            'reference' => 'KARIR-SYN-001', 'status' => 'approved',
            'account_resolution' => 'existing_core_user',
        ];

        $this->get(route('registration-status.show', 'KARIR-SYN-001'))
            ->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('RegistrationStatus')
            ->where('registration.status', 'approved')
            ->where('registration.account_resolution', 'existing_core_user'));
    }

    private function registrationData(): array
    {
        return [
            'student_number' => 'SYN-001', 'full_name' => 'Alumni Sintetis',
            'claimed_program' => 'S1 Farmasi', 'graduation_year' => 2025,
            'personal_email' => 'alumni@example.invalid', 'whatsapp' => '08000000001',
            'password' => 'Synthetic-Only-123', 'password_confirmation' => 'Synthetic-Only-123',
        ];
    }
}
