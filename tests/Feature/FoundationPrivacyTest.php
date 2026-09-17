<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FoundationPrivacyTest extends TestCase
{
    use RefreshDatabase;

    public function test_private_career_document_is_not_publicly_routed(): void
    {
        Storage::fake('career_private');
        Storage::disk('career_private')->put('synthetic/private-probe.txt', 'synthetic');

        Storage::disk('career_private')->assertExists('synthetic/private-probe.txt');
        $this->get('/storage/synthetic/private-probe.txt')->assertForbidden();
        $this->assertFalse(config('filesystems.disks.career_private.serve'));
        $this->assertStringNotContainsString(
            str_replace('\\', '/', public_path()),
            str_replace('\\', '/', config('filesystems.disks.career_private.root')),
        );
    }

    public function test_health_and_session_configuration_expose_no_infrastructure_details(): void
    {
        $response = $this->get('/health')->assertOk()->assertExactJson([
            'status' => 'ok',
            'core_identity' => 'unavailable',
        ]);

        $this->assertStringNotContainsString('33079', $response->getContent());
        $this->assertSame('safa_karir_session', config('session.cookie'));
        $this->assertNull(config('session.domain'));
        $this->assertTrue(config('session.http_only'));
        $this->assertSame('lax', config('session.same_site'));
    }

    public function test_login_uses_core_session_contract_without_local_credential_tables(): void
    {
        $this->assertFalse(Schema::hasTable('users'));
        $this->assertFalse(Schema::hasTable('password_reset_tokens'));
        $this->post('/login', [
            'email' => 'synthetic@example.invalid',
            'password' => 'synthetic-only',
        ])->assertMethodNotAllowed();
        $this->assertTrue(Route::has('internal-session.store'));
    }
}
