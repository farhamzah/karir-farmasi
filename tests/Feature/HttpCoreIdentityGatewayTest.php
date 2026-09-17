<?php

namespace Tests\Feature;

use App\Exceptions\CoreIdentityDenied;
use App\Exceptions\CoreIdentityUnavailable;
use App\Services\HttpCoreIdentityGateway;
use App\Support\CareerRoleRegistry;
use App\Support\CorePrincipalNormalizer;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class HttpCoreIdentityGatewayTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
    }

    public function test_valid_synthetic_response_is_normalized_without_extra_fields_or_tokens(): void
    {
        $payload = $this->validPrincipal();
        $payload['legacy_token'] = 'must-be-discarded';
        $payload['unneeded_profile'] = ['nim' => 'discarded'];

        Http::fake([
            'https://identity.invalid/karir/verify' => Http::response(['principal' => $payload]),
        ]);

        $principal = $this->gateway()->authenticate('synthetic-user', str_repeat('x', 24));

        $this->assertSame('https://issuer.invalid', $principal->issuer);
        $this->assertSame('subject-001', $principal->subject);
        $this->assertSame(['kandidat-karir'], $principal->roles);
        $this->assertSame(['farmasi-ubp'], $principal->programIds);
        $this->assertArrayNotHasKey('legacy_token', $principal->toSessionArray());
        $this->assertArrayNotHasKey('unneeded_profile', $principal->toSessionArray());

        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
            && $request->url() === 'https://identity.invalid/karir/verify'
            && $request->hasHeader('X-Core-App-Code', 'karir-farmasi')
            && $request->hasHeader('X-Core-Client-Id', 'synthetic-client')
            && isset($request['password']));
    }

    #[DataProvider('deniedStatusProvider')]
    public function test_denied_http_status_never_returns_a_principal(int $status): void
    {
        Http::fake(['*' => Http::response(['detail' => 'discarded'], $status)]);

        $this->expectException(CoreIdentityDenied::class);
        $this->gateway()->authenticate('synthetic-user', str_repeat('x', 24));
    }

    /** @return array<string, array{int}> */
    public static function deniedStatusProvider(): array
    {
        return [
            'unauthorized' => [401],
            'forbidden' => [403],
            'not found' => [404],
            'unprocessable' => [422],
            'redirect' => [302],
        ];
    }

    public function test_malformed_json_never_returns_a_principal(): void
    {
        Http::fake(['*' => Http::response('{malformed', 200, ['Content-Type' => 'application/json'])]);

        $this->expectException(CoreIdentityDenied::class);
        $this->gateway()->authenticate('synthetic-user', str_repeat('x', 24));
    }

    public function test_timeout_is_reported_as_unavailable(): void
    {
        Http::fake(['*' => Http::failedConnection('synthetic timeout')]);

        $this->expectException(CoreIdentityUnavailable::class);
        $this->gateway()->authenticate('synthetic-user', str_repeat('x', 24));
    }

    public function test_server_error_is_not_retried(): void
    {
        $attempts = 0;
        Http::fake(function () use (&$attempts) {
            $attempts++;

            return Http::response(['detail' => 'discarded'], 503);
        });

        try {
            $this->gateway()->authenticate('synthetic-user', str_repeat('x', 24));
            $this->fail('The gateway should fail closed.');
        } catch (CoreIdentityUnavailable) {
            $this->assertSame(1, $attempts);
            Http::assertSentCount(1);
        }
    }

    public function test_inactive_access_role_app_and_program_payloads_are_denied(): void
    {
        $invalidPayloads = [];

        $inactive = $this->validPrincipal();
        $inactive['active'] = false;
        $invalidPayloads[] = $inactive;

        $noAccess = $this->validPrincipal();
        $noAccess['has_app_access'] = false;
        $invalidPayloads[] = $noAccess;

        $wrongApp = $this->validPrincipal();
        $wrongApp['app_code'] = 'other-app';
        $invalidPayloads[] = $wrongApp;

        $inactiveRole = $this->validPrincipal();
        $inactiveRole['roles'][0]['active'] = false;
        $invalidPayloads[] = $inactiveRole;

        $unknownRole = $this->validPrincipal();
        $unknownRole['roles'][0]['slug'] = 'foreign-role';
        $invalidPayloads[] = $unknownRole;

        $wrongProgram = $this->validPrincipal();
        $wrongProgram['program_ids'] = ['outside-farmasi'];
        $invalidPayloads[] = $wrongProgram;

        foreach ($invalidPayloads as $payload) {
            Http::fake(['*' => Http::response(['principal' => $payload])]);
            try {
                $this->gateway()->authenticate('synthetic-user', str_repeat('x', 24));
                $this->fail('An invalid identity payload was accepted.');
            } catch (CoreIdentityDenied) {
                $this->assertTrue(true);
            }
        }
    }

    public function test_missing_fields_and_legacy_endpoint_configuration_fail_closed(): void
    {
        $payload = $this->validPrincipal();
        unset($payload['subject']);
        Http::fake(['*' => Http::response(['principal' => $payload])]);

        $this->expectException(CoreIdentityDenied::class);
        $this->gateway()->authenticate('synthetic-user', str_repeat('x', 24));
    }

    public function test_admin_approved_alumni_grant_allows_empty_core_program_profile(): void
    {
        $payload = $this->validPrincipal();
        $payload['program_ids'] = [];
        $payload['career_scope'] = ['farmasi'];
        $payload['eligibility_source'] = 'alumni_admin_approval';
        Http::fake(['*' => Http::response(['principal' => $payload])]);

        $principal = $this->gateway()->authenticate('synthetic-user', str_repeat('x', 24));

        $this->assertSame([], $principal->programIds);
    }

    public function test_core_operational_admin_role_allows_empty_program_profile(): void
    {
        $payload = $this->validPrincipal();
        $payload['roles'] = [['slug' => 'admin-karir', 'active' => true]];
        $payload['program_ids'] = [];
        $payload['career_scope'] = ['farmasi'];
        $payload['eligibility_source'] = 'core_operational_role';
        Http::fake(['*' => Http::response(['principal' => $payload])]);

        $principal = $this->gateway()->authenticate('synthetic-user', str_repeat('x', 24));

        $this->assertSame(['admin-karir'], $principal->roles);
        $this->assertSame([], $principal->programIds);
    }

    public function test_core_operational_source_without_operational_role_is_denied(): void
    {
        $payload = $this->validPrincipal();
        $payload['program_ids'] = [];
        $payload['career_scope'] = ['farmasi'];
        $payload['eligibility_source'] = 'core_operational_role';
        Http::fake(['*' => Http::response(['principal' => $payload])]);

        $this->expectException(CoreIdentityDenied::class);
        $this->gateway()->authenticate('synthetic-user', str_repeat('x', 24));
    }

    public function test_legacy_endpoint_is_rejected_before_any_request_is_sent(): void
    {
        Http::fake();
        $gateway = $this->gateway('https://identity.invalid/api/v1/auth/login');

        try {
            $gateway->authenticate('synthetic-user', str_repeat('x', 24));
            $this->fail('The legacy endpoint was accepted.');
        } catch (CoreIdentityUnavailable) {
            Http::assertNothingSent();
        }
    }

    public function test_disabled_adapter_and_synthetic_production_response_fail_closed(): void
    {
        Http::fake(['*' => Http::response(['principal' => $this->validPrincipal()])]);
        $disabled = new HttpCoreIdentityGateway(
            normalizer: new CorePrincipalNormalizer(new CareerRoleRegistry, 'karir-farmasi', ['farmasi-ubp'], 'testing'),
            enabled: false,
            verifyUrl: 'https://identity.invalid/karir/verify',
            clientId: 'synthetic-client',
            clientSecret: str_repeat('s', 24),
            connectTimeoutSeconds: 2,
            timeoutSeconds: 5,
        );

        try {
            $disabled->authenticate('synthetic-user', str_repeat('x', 24));
            $this->fail('A disabled adapter was allowed to send a request.');
        } catch (CoreIdentityUnavailable) {
            Http::assertNothingSent();
        }

        $production = new HttpCoreIdentityGateway(
            normalizer: new CorePrincipalNormalizer(new CareerRoleRegistry, 'karir-farmasi', ['farmasi-ubp'], 'production'),
            enabled: true,
            verifyUrl: 'https://identity.invalid/karir/verify',
            clientId: 'synthetic-client',
            clientSecret: str_repeat('s', 24),
            connectTimeoutSeconds: 2,
            timeoutSeconds: 5,
        );

        $this->expectException(CoreIdentityDenied::class);
        $production->authenticate('synthetic-user', str_repeat('x', 24));
    }

    private function gateway(string $url = 'https://identity.invalid/karir/verify'): HttpCoreIdentityGateway
    {
        return new HttpCoreIdentityGateway(
            normalizer: new CorePrincipalNormalizer(
                roles: new CareerRoleRegistry,
                expectedAppCode: 'karir-farmasi',
                allowedProgramIds: ['farmasi-ubp'],
                environment: 'testing',
            ),
            enabled: true,
            verifyUrl: $url,
            clientId: 'synthetic-client',
            clientSecret: str_repeat('s', 24),
            connectTimeoutSeconds: 2,
            timeoutSeconds: 5,
        );
    }

    /** @return array<string, mixed> */
    private function validPrincipal(): array
    {
        return [
            'issuer' => 'https://issuer.invalid',
            'subject' => 'subject-001',
            'core_user_id' => 'core-user-001',
            'display_name' => 'Alumni Sintetis',
            'email' => null,
            'active' => true,
            'app_code' => 'karir-farmasi',
            'has_app_access' => true,
            'roles' => [['slug' => 'kandidat-karir', 'active' => true]],
            'program_ids' => ['farmasi-ubp'],
            'verified_at' => '2026-09-11T00:00:00+07:00',
            'synthetic' => true,
        ];
    }
}
