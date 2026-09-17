<?php

namespace Tests\Feature;

use App\Models\CareerProfile;
use App\Services\FixtureCoreIdentityGateway;
use App\Talent\TalentIndexBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

class TalentFixtureSafetyTest extends TestCase
{
    use RefreshDatabase;

    public function test_missing_professional_name_uses_neutral_production_facing_fallback(): void
    {
        $profile = CareerProfile::factory()->create(['professional_name' => null]);

        $index = app(TalentIndexBuilder::class)->rebuild($profile);

        $this->assertSame('Alumni Farmasi', $index->professional_name);
        $this->assertStringNotContainsString('Sintetis', $index->professional_name);
    }

    public function test_synthetic_identity_gateway_is_forbidden_outside_local_and_testing(): void
    {
        $this->expectException(LogicException::class);

        new FixtureCoreIdentityGateway('production');
    }
}
