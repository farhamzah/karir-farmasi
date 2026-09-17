<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DatabasePersistenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_synthetic_audit_event_persists_on_mysql(): void
    {
        $this->assertSame('mysql', DB::connection()->getDriverName());
        $this->assertSame('safa_karir_test', DB::connection()->getDatabaseName());

        DB::table('audit_events')->insert([
            'event_type' => 'foundation.synthetic-check',
            'actor_reference' => 'fixture-core-user-001',
            'metadata' => json_encode(['synthetic' => true], JSON_THROW_ON_ERROR),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertDatabaseHas('audit_events', [
            'event_type' => 'foundation.synthetic-check',
            'actor_reference' => 'fixture-core-user-001',
        ]);
    }
}
