<?php

namespace Tests\Feature;

use App\Contracts\CoreAlumniGateway;
use App\Models\CareerProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Fakes\FakeCoreAlumniGateway;
use Tests\TestCase;

class AdminRegistrationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private FakeCoreAlumniGateway $gateway;

    protected function setUp(): void
    {
        parent::setUp();
        $this->gateway = new FakeCoreAlumniGateway;
        $this->app->instance(CoreAlumniGateway::class, $this->gateway);
    }

    public function test_guest_and_candidate_cannot_open_admin_queue(): void
    {
        $this->get(route('admin.registrations.index'))->assertRedirect(route('home'));
        $this->withSession(['core_principal' => $this->principal(['kandidat-karir'])])
            ->get(route('admin.registrations.index'))->assertForbidden();
    }

    public function test_admin_lists_and_approves_using_actor_from_server_session(): void
    {
        $session = ['core_principal' => $this->principal(['admin-karir'], 'core-admin-001')];
        $this->withSession($session)->get(route('admin.registrations.index'))->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Registrations')
                ->where('canApprove', true));

        $this->withSession($session)->post(route('admin.registrations.approve', 'KARIR-SYN-001'), [
            'approver_core_user_id' => 'spoofed-browser-id',
        ])->assertRedirect(route('admin.registrations.show', 'KARIR-SYN-001'));

        $this->assertSame(['KARIR-SYN-001', 'core-admin-001'], $this->gateway->approved);
        $profile = CareerProfile::where('core_user_id', 'fixture-alumni-core-001')->sole();
        $this->assertSame('SYN-001', $profile->alumni_number);
        $this->assertSame(2025, $profile->graduation_year);
        $this->assertSame('Alumni Sintetis', $profile->professional_name);
        $this->assertTrue($profile->visible_in_alumni_directory);
    }

    public function test_admin_can_bulk_approve_selected_pending_registrations(): void
    {
        $session = ['core_principal' => $this->principal(['admin-karir'], 'core-admin-001')];

        $this->withSession($session)->post(route('admin.registrations.bulk-approve'), [
            'references' => ['KARIR-SYN-001', 'KARIR-SYN-002'],
        ])->assertSessionHasNoErrors()
            ->assertSessionHas('success', '2 pendaftaran berhasil disetujui.')
            ->assertRedirect();

        $this->assertSame([
            ['KARIR-SYN-001', 'core-admin-001'],
            ['KARIR-SYN-002', 'core-admin-001'],
        ], $this->gateway->approvals);
        $this->assertDatabaseHas('career_profiles', [
            'core_user_id' => 'fixture-alumni-core-001', 'alumni_number' => 'SYN-001',
        ]);
        $this->assertDatabaseHas('career_profiles', [
            'core_user_id' => 'fixture-alumni-core-002', 'alumni_number' => 'SYN-002',
        ]);
    }

    public function test_bulk_approval_requires_selection_and_approve_capability(): void
    {
        $this->withSession(['core_principal' => $this->principal(['admin-karir'], 'core-admin-001')])
            ->post(route('admin.registrations.bulk-approve'), ['references' => []])
            ->assertSessionHasErrors('references');

        $this->withSession(['core_principal' => $this->principal(['viewer-karir'], 'core-viewer-001')])
            ->post(route('admin.registrations.bulk-approve'), ['references' => ['KARIR-SYN-001']])
            ->assertForbidden();
    }

    public function test_bulk_approval_continues_when_one_core_decision_fails(): void
    {
        $this->gateway->failedApprovals = ['KARIR-SYN-002'];

        $this->withSession(['core_principal' => $this->principal(['admin-karir'], 'core-admin-001')])
            ->post(route('admin.registrations.bulk-approve'), [
                'references' => ['KARIR-SYN-001', 'KARIR-SYN-002', 'KARIR-SYN-003'],
            ])->assertSessionHas('success', '2 pendaftaran berhasil disetujui.')
            ->assertSessionHasErrors('bulk');

        $this->assertSame([
            ['KARIR-SYN-001', 'core-admin-001'],
            ['KARIR-SYN-003', 'core-admin-001'],
        ], $this->gateway->approvals);
    }

    public function test_admin_rejects_with_reason_and_session_actor(): void
    {
        $this->withSession(['core_principal' => $this->principal(['admin-karir'], 'core-admin-001')])
            ->post(route('admin.registrations.reject', 'KARIR-SYN-001'), [
                'reason' => 'Data akademik belum cocok.',
                'approver_core_user_id' => 'spoofed-browser-id',
            ])->assertRedirect(route('admin.registrations.show', 'KARIR-SYN-001'));

        $this->assertSame(
            ['KARIR-SYN-001', 'core-admin-001', 'Data akademik belum cocok.'],
            $this->gateway->rejected,
        );
    }

    /** @param list<string> $roles */
    private function principal(array $roles, string $id = 'core-user-001'): array
    {
        return [
            'issuer' => 'https://core.fixture.invalid',
            'subject' => 'fixture:'.$id,
            'core_user_id' => $id,
            'display_name' => 'Sintetis',
            'email' => 'actor@fixture.invalid',
            'active' => true,
            'app_code' => 'karir-farmasi',
            'has_app_access' => true,
            'roles' => $roles,
            'program_ids' => ['farmasi-ubp'],
            'verified_at' => '2026-09-12T00:00:00+07:00',
            'synthetic' => true,
        ];
    }
}
