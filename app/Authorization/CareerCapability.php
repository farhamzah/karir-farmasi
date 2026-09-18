<?php

namespace App\Authorization;

enum CareerCapability: string
{
    case CandidateDashboardView = 'candidate.dashboard.view';
    case ProfileViewOwn = 'profile.view.own';
    case ProfileUpdateOwn = 'profile.update.own';
    case CvViewOwn = 'cv.view.own';
    case CvManageOwn = 'cv.manage.own';
    case EventViewPublished = 'event.view.published';
    case EventRegisterOwn = 'event.register.own';
    case EventHistoryOwn = 'event.history.own';
    case CertificateViewOwn = 'certificate.view.own';
    case EventManage = 'event.manage';
    case EventParticipantManage = 'event.participant.manage';
    case CertificateManage = 'certificate.manage';
    case EventAggregateView = 'event.aggregate.view';
    case CompanyManage = 'company.manage';
    case AlumniDirectoryView = 'alumni.directory.view';
    case TalentDirectorySearchInternal = 'talent.directory.search.internal';
    case JobBrowse = 'job.browse';
    case JobApplyOwn = 'job.apply.own';
    case JobInvitationOwn = 'job.invitation.own';
    case JobManage = 'job.manage';
    case JobReview = 'job.review';
    case ApplicationOperationalView = 'application.operational.view';
    case NotificationReadOwn = 'notification.read.own';
    case TracerSubmitOwn = 'tracer.submit.own';
    case TracerManage = 'tracer.manage';
    case OperationalExport = 'operational.export';
    case TemplateManage = 'template.manage';
    case RegistrationQueueView = 'registration.queue.view';
    case RegistrationDetailView = 'registration.detail.view';
    case RegistrationApprove = 'registration.approve';
    case RegistrationReject = 'registration.reject';
    case CareerAdminAccess = 'career.admin.access';
    case AggregateDashboardView = 'aggregate.dashboard.view';
    case CandidatePrivateReadAny = 'candidate.private.read.any';
    case TracerRawReadAny = 'tracer.raw.read.any';
    case CredentialRead = 'credential.read';

    public function isSensitive(): bool
    {
        return in_array($this, [
            self::RegistrationQueueView,
            self::RegistrationDetailView,
            self::RegistrationApprove,
            self::RegistrationReject,
            self::ProfileViewOwn,
            self::ProfileUpdateOwn,
            self::CvViewOwn,
            self::CvManageOwn,
            self::TemplateManage,
            self::EventRegisterOwn,
            self::EventHistoryOwn,
            self::CertificateViewOwn,
            self::EventManage,
            self::EventParticipantManage,
            self::CertificateManage,
            self::CompanyManage,
            self::AlumniDirectoryView,
            self::TalentDirectorySearchInternal,
            self::JobBrowse,
            self::JobApplyOwn,
            self::JobInvitationOwn,
            self::JobManage,
            self::JobReview,
            self::ApplicationOperationalView,
            self::NotificationReadOwn,
            self::TracerSubmitOwn,
            self::TracerManage,
            self::OperationalExport,
        ], true);
    }
}
