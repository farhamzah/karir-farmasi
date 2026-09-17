<?php

namespace App\Authorization;

use App\Support\CareerRoleRegistry;

final class CareerRoleCapabilities
{
    /** @var array<string, list<CareerCapability>> */
    private const MAP = [
        CareerRoleRegistry::Candidate => [
            CareerCapability::CandidateDashboardView,
            CareerCapability::ProfileViewOwn,
            CareerCapability::ProfileUpdateOwn,
            CareerCapability::CvViewOwn,
            CareerCapability::CvManageOwn,
            CareerCapability::EventViewPublished,
            CareerCapability::EventRegisterOwn,
            CareerCapability::EventHistoryOwn,
            CareerCapability::CertificateViewOwn,
            CareerCapability::JobBrowse,
            CareerCapability::JobApplyOwn,
            CareerCapability::JobInvitationOwn,
            CareerCapability::NotificationReadOwn,
            CareerCapability::TracerSubmitOwn,
        ],
        CareerRoleRegistry::Administrator => [
            CareerCapability::RegistrationQueueView,
            CareerCapability::RegistrationDetailView,
            CareerCapability::RegistrationApprove,
            CareerCapability::RegistrationReject,
            CareerCapability::CareerAdminAccess,
            CareerCapability::AggregateDashboardView,
            CareerCapability::TemplateManage,
            CareerCapability::EventManage,
            CareerCapability::EventParticipantManage,
            CareerCapability::CertificateManage,
            CareerCapability::EventAggregateView,
            CareerCapability::CompanyManage,
            CareerCapability::TalentDirectorySearchInternal,
            CareerCapability::JobManage,
            CareerCapability::JobReview,
            CareerCapability::ApplicationOperationalView,
            CareerCapability::TracerManage,
            CareerCapability::OperationalExport,
        ],
        CareerRoleRegistry::Officer => [
            CareerCapability::RegistrationQueueView,
            CareerCapability::RegistrationDetailView,
            CareerCapability::CareerAdminAccess,
            CareerCapability::AggregateDashboardView,
            CareerCapability::EventParticipantManage,
            CareerCapability::CertificateManage,
            CareerCapability::EventAggregateView,
            CareerCapability::TalentDirectorySearchInternal,
            CareerCapability::JobManage,
            CareerCapability::JobReview,
            CareerCapability::ApplicationOperationalView,
            CareerCapability::OperationalExport,
        ],
        CareerRoleRegistry::Viewer => [CareerCapability::AggregateDashboardView, CareerCapability::EventAggregateView, CareerCapability::TalentDirectorySearchInternal, CareerCapability::OperationalExport],
    ];

    /**
     * @param  list<string>  $roles
     * @return list<CareerCapability>
     */
    public function forRoles(array $roles): array
    {
        $capabilities = [];

        foreach ($roles as $role) {
            foreach (self::MAP[$role] ?? [] as $capability) {
                $capabilities[$capability->value] = $capability;
            }
        }

        return array_values($capabilities);
    }
}
