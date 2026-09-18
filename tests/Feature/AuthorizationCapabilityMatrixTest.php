<?php

namespace Tests\Feature;

use App\Authorization\CareerAuthorization;
use App\Authorization\CareerCapability;
use App\Authorization\CareerRoleCapabilities;
use App\Data\CareerActor;
use App\Support\CareerRoleRegistry;
use Tests\TestCase;

class AuthorizationCapabilityMatrixTest extends TestCase
{
    public function test_each_role_receives_only_its_explicit_capabilities(): void
    {
        $mapping = new CareerRoleCapabilities;
        $expected = [
            CareerRoleRegistry::Candidate => [
                'alumni.directory.view', 'candidate.dashboard.view', 'certificate.view.own', 'cv.manage.own', 'cv.view.own', 'event.history.own',
                'event.register.own', 'event.view.published', 'job.apply.own', 'job.browse', 'job.invitation.own',
                'notification.read.own', 'profile.update.own', 'profile.view.own', 'tracer.submit.own',
            ],
            CareerRoleRegistry::Administrator => [
                'aggregate.dashboard.view', 'alumni.directory.view', 'application.operational.view', 'career.admin.access', 'certificate.manage',
                'company.manage', 'event.aggregate.view', 'event.manage', 'event.participant.manage', 'job.manage', 'job.review',
                'operational.export', 'registration.approve', 'registration.detail.view',
                'registration.queue.view', 'registration.reject', 'talent.directory.search.internal', 'template.manage',
                'tracer.manage',
            ],
            CareerRoleRegistry::Officer => [
                'aggregate.dashboard.view', 'alumni.directory.view', 'application.operational.view', 'career.admin.access', 'certificate.manage',
                'event.aggregate.view', 'event.participant.manage', 'job.manage', 'job.review', 'operational.export',
                'registration.detail.view', 'registration.queue.view', 'talent.directory.search.internal',
            ],
            CareerRoleRegistry::Viewer => ['aggregate.dashboard.view', 'alumni.directory.view', 'event.aggregate.view', 'operational.export', 'talent.directory.search.internal'],
        ];

        foreach ($expected as $role => $capabilityNames) {
            $actual = array_map(
                fn (CareerCapability $capability): string => $capability->value,
                $mapping->forRoles([$role]),
            );
            sort($actual);

            $this->assertSame($capabilityNames, $actual, "Unexpected capabilities for [$role].");
        }
    }

    public function test_no_current_role_can_read_private_candidate_raw_tracer_or_credentials(): void
    {
        $authorization = new CareerAuthorization;

        foreach ([
            CareerRoleRegistry::Candidate,
            CareerRoleRegistry::Administrator,
            CareerRoleRegistry::Officer,
            CareerRoleRegistry::Viewer,
        ] as $role) {
            $actor = $this->actor([$role]);

            $this->assertFalse($authorization->allows($actor, CareerCapability::CandidatePrivateReadAny));
            $this->assertFalse($authorization->allows($actor, CareerCapability::TracerRawReadAny));
            $this->assertFalse($authorization->allows($actor, CareerCapability::CredentialRead));
        }
    }

    public function test_multi_role_actor_gets_union_without_global_superuser_access(): void
    {
        $authorization = new CareerAuthorization;
        $actor = $this->actor([CareerRoleRegistry::Administrator, CareerRoleRegistry::Candidate]);

        $this->assertTrue($authorization->allows($actor, CareerCapability::CandidateDashboardView));
        $this->assertTrue($authorization->allows($actor, CareerCapability::RegistrationApprove));
        $this->assertFalse($authorization->allows($actor, CareerCapability::CandidatePrivateReadAny));
        $this->assertFalse($authorization->allows($actor, CareerCapability::TracerRawReadAny));
        $this->assertFalse($authorization->allows($actor, CareerCapability::CredentialRead));
    }

    /** @param list<string> $roles */
    private function actor(array $roles): CareerActor
    {
        return new CareerActor(
            subject: 'fixture:actor',
            coreUserId: 'core-user-001',
            displayName: 'Aktor Sintetis',
            email: null,
            roles: $roles,
            capabilities: (new CareerRoleCapabilities)->forRoles($roles),
        );
    }
}
