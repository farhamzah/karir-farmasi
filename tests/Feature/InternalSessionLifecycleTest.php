<?php

namespace Tests\Feature;

use App\Contracts\CoreIdentityGateway;
use App\Data\CorePrincipal;
use App\Exceptions\CoreIdentityDenied;
use App\Exceptions\CoreIdentityUnavailable;
use App\Services\FixtureCoreIdentityGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class InternalSessionLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_successful_verification_regenerates_session_and_stores_only_minimum_principal(): void
    {
        $gateway = new FixtureCoreIdentityGateway('testing');
        $this->app->instance(CoreIdentityGateway::class, $gateway);
        $credential = str_repeat('x', 24);

        $this->withSession(['previous_session_id' => session()->getId()]);
        $previousId = session('previous_session_id');

        $response = $this->post(route('internal-session.store'), [
            'identifier' => 'synthetic-user',
            'password' => $credential,
        ]);

        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('core_principal.subject', 'fixture:alumni-001');
        $this->assertNotSame($previousId, session()->getId());

        $sessionPrincipal = session('core_principal');
        $encoded = json_encode($sessionPrincipal, JSON_THROW_ON_ERROR);
        $this->assertSame([
            'issuer', 'subject', 'core_user_id', 'display_name', 'email', 'active',
            'app_code', 'has_app_access', 'roles', 'program_ids', 'verified_at', 'synthetic',
        ], array_keys($sessionPrincipal));
        $this->assertStringNotContainsString($credential, $encoded);
        $this->assertStringNotContainsString('password', $encoded);
        $this->assertStringNotContainsString('token', $encoded);
        $this->assertSame(0, DB::table('audit_events')->count());
        $this->assertStringNotContainsString($credential, $response->getContent());
    }

    public function test_denied_identity_does_not_create_authenticated_session_or_expose_credential(): void
    {
        $credential = str_repeat('y', 24);
        $this->app->instance(CoreIdentityGateway::class, new class implements CoreIdentityGateway
        {
            public function currentPrincipal(): CorePrincipal
            {
                throw new CoreIdentityDenied;
            }

            public function authenticate(string $identifier, #[\SensitiveParameter] string $password): CorePrincipal
            {
                throw new CoreIdentityDenied;
            }
        });

        $response = $this->from(route('home'))->post(route('internal-session.store'), [
            'identifier' => 'synthetic-user',
            'password' => $credential,
        ]);

        $response->assertRedirect(route('home'));
        $response->assertSessionHasErrors('identifier');
        $response->assertSessionMissing('core_principal');
        $this->assertStringNotContainsString($credential, $response->getContent());
    }

    public function test_unavailable_identity_returns_controlled_error_without_authenticated_session(): void
    {
        $this->app->instance(CoreIdentityGateway::class, new class implements CoreIdentityGateway
        {
            public function currentPrincipal(): CorePrincipal
            {
                throw new CoreIdentityUnavailable;
            }

            public function authenticate(string $identifier, #[\SensitiveParameter] string $password): CorePrincipal
            {
                throw new CoreIdentityUnavailable;
            }
        });

        $response = $this->postJson(route('internal-session.store'), [
            'identifier' => 'synthetic-user',
            'password' => str_repeat('z', 24),
        ]);

        $response->assertStatus(503)->assertExactJson([
            'message' => 'Layanan identitas sementara tidak tersedia.',
        ]);
        $response->assertSessionMissing('core_principal');
    }

    public function test_local_logout_removes_principal_without_calling_core(): void
    {
        Http::preventStrayRequests();
        $principal = (new FixtureCoreIdentityGateway('testing'))->currentPrincipal()->toSessionArray();

        $response = $this->withSession(['core_principal' => $principal])
            ->delete(route('internal-session.destroy'));

        $response->assertRedirect(route('login'));
        $response->assertSessionMissing('core_principal');
        Http::assertNothingSent();
    }
}
