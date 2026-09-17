<?php

namespace Tests\Feature;

use App\Contracts\CoreIdentityGateway;
use App\Exceptions\CoreIdentityUnavailable;
use App\Services\FixtureCoreIdentityGateway;
use Tests\TestCase;

class IdentityBoundaryTest extends TestCase
{
    public function test_default_identity_adapter_fails_closed(): void
    {
        $this->expectException(CoreIdentityUnavailable::class);
        $this->app->make(CoreIdentityGateway::class)->currentPrincipal();
    }

    public function test_fixture_principal_is_fixed_and_synthetic_in_testing(): void
    {
        $principal = (new FixtureCoreIdentityGateway('testing'))->currentPrincipal();

        $this->assertTrue($principal->synthetic);
        $this->assertSame('fixture-core-user-001', $principal->coreUserId);
    }

    public function test_fixture_supports_synthetic_operational_roles_for_local_visual_review(): void
    {
        $gateway = new FixtureCoreIdentityGateway('testing');

        $this->assertSame(['admin-karir'], $gateway->authenticate('admin@fixture.invalid', 'synthetic')->roles);
        $this->assertSame(['petugas-karir'], $gateway->authenticate('petugas@fixture.invalid', 'synthetic')->roles);
        $this->assertSame(['viewer-karir'], $gateway->authenticate('viewer@fixture.invalid', 'synthetic')->roles);
    }
}
