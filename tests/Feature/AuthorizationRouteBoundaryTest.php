<?php

namespace Tests\Feature;

use App\Contracts\CoreAlumniGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Fakes\FakeCoreAlumniGateway;
use Tests\TestCase;

class AuthorizationRouteBoundaryTest extends TestCase
{
    use RefreshDatabase;

    private FakeCoreAlumniGateway $gateway;

    protected function setUp(): void
    {
        parent::setUp();
        $this->gateway = new FakeCoreAlumniGateway;
        $this->app->instance(CoreAlumniGateway::class, $this->gateway);
    }

    public function test_candidate_dashboard_requires_candidate_capability_and_an_active_multi_role_workspace(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('home'));
        $this->withSession(['core_principal' => $this->principal(['kandidat-karir'])])
            ->get(route('dashboard'))->assertOk();
        $this->withSession(['core_principal' => $this->principal(['admin-karir'])])
            ->get(route('dashboard'))->assertForbidden();
        $this->withSession(['core_principal' => $this->principal(['admin-karir', 'kandidat-karir'])])
            ->get(route('dashboard'))->assertForbidden();
        $this->withSession([
            'core_principal' => $this->principal(['admin-karir', 'kandidat-karir']),
            'career_active_role' => 'kandidat-karir',
        ])->get(route('dashboard'))->assertOk();
    }

    public function test_staff_overview_accepts_staff_and_viewer_but_rejects_candidate_only(): void
    {
        foreach (['admin-karir', 'petugas-karir', 'viewer-karir'] as $role) {
            $this->withSession(['core_principal' => $this->principal([$role])])
                ->get(route('staff.overview'))
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page
                    ->component('Staff/Overview')
                    ->missing('registration'));
        }

        $this->withSession(['core_principal' => $this->principal(['kandidat-karir'])])
            ->get(route('staff.overview'))->assertForbidden();
    }

    public function test_admin_and_officer_can_view_registration_data_while_viewer_and_candidate_cannot(): void
    {
        foreach (['admin-karir', 'petugas-karir'] as $role) {
            $this->withSession(['core_principal' => $this->principal([$role])])
                ->get(route('admin.registrations.index'))->assertOk();
            $this->withSession(['core_principal' => $this->principal([$role])])
                ->get(route('admin.registrations.show', 'KARIR-SYN-001'))
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page
                    ->component('Admin/RegistrationDetail')
                    ->where('canApprove', $role === 'admin-karir')
                    ->where('canReject', $role === 'admin-karir'));
        }

        foreach (['viewer-karir', 'kandidat-karir'] as $role) {
            $this->withSession(['core_principal' => $this->principal([$role])])
                ->get(route('admin.registrations.index'))->assertForbidden();
            $this->withSession(['core_principal' => $this->principal([$role])])
                ->get(route('admin.registrations.show', 'KARIR-SYN-001'))->assertForbidden();
        }
    }

    public function test_only_admin_can_decide_and_browser_cannot_spoof_role_or_actor(): void
    {
        $this->withSession(['core_principal' => $this->principal(['petugas-karir'], 'core-officer-001')])
            ->post(route('admin.registrations.approve', 'KARIR-SYN-001'), [
                'roles' => ['admin-karir'],
                'approver_core_user_id' => 'spoofed-admin',
            ])->assertForbidden();
        $this->assertNull($this->gateway->approved);

        $this->withSession(['core_principal' => $this->principal(['admin-karir'], 'core-admin-001')])
            ->post(route('admin.registrations.approve', 'KARIR-SYN-001'), [
                'roles' => ['viewer-karir'],
                'approver_core_user_id' => 'spoofed-browser-id',
            ])->assertRedirect(route('admin.registrations.show', 'KARIR-SYN-001'));

        $this->assertSame(['KARIR-SYN-001', 'core-admin-001'], $this->gateway->approved);
    }

    public function test_missing_unknown_inactive_and_wrong_scope_contexts_fail_closed(): void
    {
        $cases = [];
        $cases['missing_role'] = array_replace($this->principal(['kandidat-karir']), ['roles' => []]);
        $cases['unknown_role'] = array_replace($this->principal(['kandidat-karir']), ['roles' => ['foreign-role']]);
        $cases['inactive'] = array_replace($this->principal(['kandidat-karir']), ['active' => false]);
        $cases['wrong_scope'] = array_replace($this->principal(['kandidat-karir']), ['app_code' => 'other-app']);

        foreach ($cases as $name => $principal) {
            $this->withSession(['core_principal' => $principal])
                ->get(route('dashboard'))
                ->assertForbidden();
        }

        $this->assertSame(
            count($cases),
            DB::table('audit_events')->where('event_type', 'authorization.denied')->count(),
            'Every malformed context should be denied.',
        );
    }

    public function test_viewer_overview_contains_no_registration_pii_or_private_payload(): void
    {
        $response = $this->withSession(['core_principal' => $this->principal(['viewer-karir'], 'core-viewer-001')])
            ->get(route('staff.overview'));

        $response->assertOk();
        $response->assertDontSee('SYN-001');
        $response->assertDontSee('Alumni Sintetis');
        $response->assertDontSee('credential_value');
        $response->assertDontSee('raw_tracer_value');
    }

    public function test_authorization_audit_uses_safe_allowlisted_metadata(): void
    {
        $password = 'credential_value_must_not_be_logged';
        $token = 'token_value_must_not_be_logged';

        $this->withSession(['core_principal' => $this->principal(['petugas-karir'], 'core-officer-001')])
            ->post(route('admin.registrations.approve', 'KARIR-SYN-001'), [
                'password' => $password,
                'token' => $token,
            ])->assertForbidden();

        $event = DB::table('audit_events')->latest('id')->first();
        $metadata = json_decode($event->metadata, true, flags: JSON_THROW_ON_ERROR);
        $metadataKeys = array_keys($metadata);
        sort($metadataKeys);

        $this->assertSame('authorization.denied', $event->event_type);
        $this->assertSame('core-officer-001', $event->actor_reference);
        $this->assertSame([
            'capability', 'decision', 'http_method', 'reason', 'route_name',
        ], $metadataKeys);
        $this->assertStringNotContainsString($password, $event->metadata);
        $this->assertStringNotContainsString($token, $event->metadata);
        $this->assertStringNotContainsString('KARIR-SYN-001', $event->metadata);
    }

    /** @param list<string> $roles */
    private function principal(array $roles, string $coreUserId = 'core-user-001'): array
    {
        return [
            'issuer' => 'https://fixture.invalid',
            'subject' => 'fixture:'.$coreUserId,
            'core_user_id' => $coreUserId,
            'display_name' => 'Viewer Sintetis',
            'email' => 'viewer@fixture.invalid',
            'active' => true,
            'app_code' => 'karir-farmasi',
            'has_app_access' => true,
            'roles' => $roles,
            'program_ids' => [],
            'verified_at' => '2026-09-12T00:00:00+07:00',
            'synthetic' => true,
        ];
    }
}
