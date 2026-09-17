<?php

namespace Tests\Feature;

use App\Authorization\CareerAuthorization;
use App\Authorization\CareerRoleCapabilities;
use App\Authorization\OwnedResourcePolicy;
use App\Contracts\OwnedCareerResource;
use App\Data\CareerActor;
use Tests\TestCase;

class OwnedResourcePolicyTest extends TestCase
{
    public function test_policy_allows_owner_and_denies_another_candidate_or_admin(): void
    {
        $policy = new class(new CareerAuthorization) extends OwnedResourcePolicy
        {
            public function view(CareerActor $actor, OwnedCareerResource $resource): bool
            {
                return $this->ownsResource($actor, $resource);
            }
        };
        $resource = new class implements OwnedCareerResource
        {
            public function ownerCoreUserId(): string
            {
                return 'core-owner-001';
            }
        };

        $this->assertTrue($policy->view($this->actor('core-owner-001', ['kandidat-karir']), $resource));
        $this->assertFalse($policy->view($this->actor('core-other-002', ['kandidat-karir']), $resource));
        $this->assertFalse($policy->view($this->actor('core-admin-003', ['admin-karir']), $resource));
    }

    /** @param list<string> $roles */
    private function actor(string $coreUserId, array $roles): CareerActor
    {
        return new CareerActor(
            subject: 'fixture:'.$coreUserId,
            coreUserId: $coreUserId,
            displayName: 'Aktor Sintetis',
            email: null,
            roles: $roles,
            capabilities: (new CareerRoleCapabilities)->forRoles($roles),
        );
    }
}
