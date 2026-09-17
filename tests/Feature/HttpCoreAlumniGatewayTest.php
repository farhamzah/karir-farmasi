<?php

namespace Tests\Feature;

use App\Exceptions\CoreAlumniOperationFailed;
use App\Services\HttpCoreAlumniGateway;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class HttpCoreAlumniGatewayTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['core_identity.app_code' => 'karir-farmasi']);
        Http::preventStrayRequests();
    }

    public function test_registration_and_admin_decisions_use_exact_dedicated_contract(): void
    {
        Http::fake([
            'http://127.0.0.1:8011/api/v1/internal/apps/karir-farmasi/alumni-registrations*' => Http::response([
                'data' => ['reference' => 'KARIR-SYN-001', 'status' => 'pending'],
            ]),
        ]);
        $gateway = $this->gateway();

        $gateway->register(['student_number' => 'SYN-001', 'password' => 'Synthetic-Only-123']);
        $gateway->approve('KARIR-SYN-001', 'core-admin-001');
        $gateway->reject('KARIR-SYN-001', 'core-admin-001', 'Belum cocok');

        Http::assertSentCount(3);
        Http::assertSent(fn (Request $request) => $request->url() === 'http://127.0.0.1:8011/api/v1/internal/apps/karir-farmasi/alumni-registrations'
            && $request->method() === 'POST'
            && $request->hasHeader('X-Core-Client-Id', 'synthetic-client')
            && $request->hasHeader('X-Core-Client-Secret', 'synthetic-secret')
            && $request->hasHeader('X-Core-App-Code', 'karir-farmasi'));
        Http::assertSent(fn (Request $request) => str_ends_with($request->url(), '/KARIR-SYN-001/approve')
            && $request['approver_core_user_id'] === 'core-admin-001');
        Http::assertSent(fn (Request $request) => str_ends_with($request->url(), '/KARIR-SYN-001/reject')
            && $request['reason'] === 'Belum cocok');
        Http::assertSent(fn (Request $request) => ! str_contains($request->url(), '/api/v1/auth/login'));
    }

    public function test_list_and_status_preserve_filter_and_minimum_response(): void
    {
        Http::fake([
            '*alumni-registrations?status=pending&page=2' => Http::response([
                'data' => [], 'meta' => ['current_page' => 2],
            ]),
            '*alumni-registrations/KARIR-SYN-001/status' => Http::response([
                'data' => ['reference' => 'KARIR-SYN-001', 'status' => 'approved'],
            ]),
        ]);

        $this->assertSame(2, $this->gateway()->registrations('pending', 2)['meta']['current_page']);
        $this->assertSame('approved', $this->gateway()->status('KARIR-SYN-001')['status']);
    }

    public function test_plain_http_is_rejected_outside_local_or_testing(): void
    {
        Http::fake();

        $this->expectException(CoreAlumniOperationFailed::class);
        $this->gateway('production')->status('KARIR-SYN-001');
    }

    private function gateway(string $environment = 'testing'): HttpCoreAlumniGateway
    {
        return new HttpCoreAlumniGateway(
            enabled: true,
            baseUrl: 'http://127.0.0.1:8011/api/v1/internal/apps/karir-farmasi',
            clientId: 'synthetic-client',
            clientSecret: 'synthetic-secret',
            connectTimeoutSeconds: 2,
            timeoutSeconds: 5,
            environment: $environment,
        );
    }
}
