<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\CompanyUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CompanyAccountWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_registers_with_local_hashed_account_and_waits_for_verification(): void
    {
        $response = $this->post(route('company.register.store'), [
            'legal_name' => 'PT Farmasi Aman Indonesia', 'display_name' => 'Farmasi Aman',
            'business_sector' => 'Industri Farmasi', 'city' => 'Karawang', 'name' => 'Rina HR',
            'email' => 'hr@farmasi-aman.test', 'password' => 'CompanyPass123', 'password_confirmation' => 'CompanyPass123',
        ]);

        $response->assertRedirect(route('company.dashboard'))->assertSessionHas('company_user_id');
        $company = Company::sole();
        $user = CompanyUser::sole();
        $this->assertSame('pending', $company->verification_status);
        $this->assertSame('company_admin', $user->role);
        $this->assertTrue(Hash::check('CompanyPass123', $user->password));
        $this->withSession(['company_user_id' => $user->id])->get(route('company.talent.index'))
            ->assertRedirect(route('company.dashboard'));
    }

    public function test_admin_verifies_rejects_and_suspends_company_with_immediate_search_effect(): void
    {
        $company = Company::factory()->create();
        $user = CompanyUser::factory()->for($company)->create();

        $this->withSession(['core_principal' => $this->principal('admin-karir')])->put(route('admin.companies.update', $company->public_reference), [
            'verification_status' => 'verified', 'decision_note' => 'Dokumen sintetis sesuai.',
        ])->assertRedirect();
        $this->assertTrue($company->fresh()->canSearchTalent());
        $this->withSession(['company_user_id' => $user->id])->get(route('company.talent.index'))->assertOk();

        $this->withSession(['core_principal' => $this->principal('admin-karir')])->put(route('admin.companies.update', $company->public_reference), [
            'verification_status' => 'suspended', 'decision_note' => 'Ditangguhkan untuk pengujian.',
        ])->assertRedirect();
        $this->withSession(['company_user_id' => $user->id])->get(route('company.talent.index'))
            ->assertRedirect(route('company.dashboard'));
        $this->assertDatabaseHas('audit_events', ['event_type' => 'company.verification.suspended']);
    }

    public function test_company_admin_creates_separate_recruiter_and_other_company_cannot_update_it(): void
    {
        $company = Company::factory()->verified()->create();
        $admin = CompanyUser::factory()->administrator()->for($company)->create();
        $this->withSession(['company_user_id' => $admin->id])->post(route('company.recruiters.store'), [
            'name' => 'Budi Rekruter', 'email' => 'budi@company.test', 'password' => 'RecruiterPass123', 'password_confirmation' => 'RecruiterPass123',
        ])->assertRedirect();
        $recruiter = CompanyUser::query()->where('email', 'budi@company.test')->firstOrFail();
        $this->assertNotSame($admin->password, $recruiter->password);

        $other = CompanyUser::factory()->administrator()->for(Company::factory()->verified())->create();
        $this->withSession(['company_user_id' => $other->id])->put(route('company.recruiters.update', $recruiter->public_reference), ['active' => false])
            ->assertNotFound();
        $this->assertTrue($recruiter->fresh()->active);
    }

    public function test_company_dashboard_never_serializes_password_hash(): void
    {
        $user = CompanyUser::factory()->for(Company::factory()->verified())->create();

        $this->withSession(['company_user_id' => $user->id])->get(route('company.dashboard'))
            ->assertInertia(fn (Assert $page) => $page->component('Company/Dashboard')->missing('user.email')->missing('user.password'));
    }

    public function test_officer_cannot_verify_company_and_public_references_are_non_sequential(): void
    {
        $company = Company::factory()->create();

        $this->withSession(['core_principal' => $this->principal('petugas-karir')])->put(route('admin.companies.update', $company->public_reference), [
            'verification_status' => 'verified',
        ])->assertForbidden();
        $this->assertSame('pending', $company->fresh()->verification_status);
        $this->assertMatchesRegularExpression('/^[0-9a-f-]{36}$/', $company->public_reference);
    }

    private function principal(string $role): array
    {
        return ['issuer' => 'https://fixture.invalid', 'subject' => 'fixture:admin', 'core_user_id' => 'core-admin-001',
            'display_name' => 'Admin Sintetis', 'email' => null, 'active' => true, 'app_code' => 'karir-farmasi',
            'has_app_access' => true, 'roles' => [$role], 'program_ids' => ['farmasi-ubp'],
            'verified_at' => '2026-09-12T00:00:00+07:00', 'synthetic' => true];
    }
}
