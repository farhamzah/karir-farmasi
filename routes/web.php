<?php

use App\Authorization\CareerCapability;
use App\Http\Controllers\Admin\CompanyController;
use App\Http\Controllers\Admin\CvTemplateController;
use App\Http\Controllers\Admin\EventParticipantController;
use App\Http\Controllers\Admin\JobImportController;
use App\Http\Controllers\Admin\RegistrationController as AdminRegistrationController;
use App\Http\Controllers\Admin\RegistrationDecisionController;
use App\Http\Controllers\AlumniDirectoryController;
use App\Http\Controllers\AlumniRegistrationController;
use App\Http\Controllers\ApplicationDocumentController;
use App\Http\Controllers\CandidateDashboardController;
use App\Http\Controllers\CandidateDiscoverabilityController;
use App\Http\Controllers\CareerCvController;
use App\Http\Controllers\CareerCvDuplicateController;
use App\Http\Controllers\CareerCvExportController;
use App\Http\Controllers\CareerCvPreviewController;
use App\Http\Controllers\CareerCvPublishController;
use App\Http\Controllers\Company\CompanyAuthController;
use App\Http\Controllers\Company\CompanyDashboardController;
use App\Http\Controllers\Company\CompanyRecruiterController;
use App\Http\Controllers\Company\EmployerFeedbackController;
use App\Http\Controllers\Company\JobApplicantController;
use App\Http\Controllers\CvShareLinkController;
use App\Http\Controllers\EventCertificateController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\EventFlyerController;
use App\Http\Controllers\EventRegistrationController;
use App\Http\Controllers\InternalSessionController;
use App\Http\Controllers\JobApplicationController;
use App\Http\Controllers\JobController;
use App\Http\Controllers\JobInvitationController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OperationalExportController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProfileFileController;
use App\Http\Controllers\ProfilePhotoController;
use App\Http\Controllers\ProfileSectionController;
use App\Http\Controllers\PublicCvController;
use App\Http\Controllers\RegistrationStatusController;
use App\Http\Controllers\StaffOverviewController;
use App\Http\Controllers\TalentProfileController;
use App\Http\Controllers\TalentSearchController;
use App\Http\Controllers\TracerController;
use App\Models\CareerEvent;
use App\Models\CareerJob;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    $jobs = CareerJob::query()->visibleToCandidates()->with('tags')->latest('published_at');
    $events = CareerEvent::query()->where('status', 'published')->where('ends_at', '>=', now())
        ->with('topics')->orderBy('starts_at');

    return Inertia::render('Home', [
        'identityStatus' => config('core_identity.http.enabled') ? 'connected' : 'unavailable',
        'environmentLabel' => app()->environment(['local', 'testing'])
            ? 'MODE UJI — DATA SINTETIS'
            : null,
        'jobs' => (clone $jobs)->limit(3)->get()->map(fn (CareerJob $job): array => [
            'reference' => $job->public_reference,
            'title' => $job->title,
            'employer' => $job->employer_display_name,
            'city' => $job->city,
            'work_mode' => $job->work_mode,
            'employment_type' => $job->employment_type,
            'expires_at' => $job->expires_at?->toDateString(),
            'tags' => $job->tags->pluck('label')->values()->all(),
        ])->all(),
        'events' => (clone $events)->limit(3)->get()->map(fn (CareerEvent $event): array => [
            'slug' => $event->slug,
            'title' => $event->title,
            'organizer' => $event->organizer,
            'event_type' => $event->event_type,
            'starts_at' => $event->starts_at->locale('id')->translatedFormat('d M Y'),
            'location' => $event->location_text,
            'topics' => $event->topics->pluck('label')->values()->all(),
            'flyer_url' => $event->flyer_path ? route('event-flyers.show', $event) : null,
            'flyer_alt_text' => $event->flyer_alt_text ?: 'Flyer '.$event->title,
        ])->all(),
        'opportunityCounts' => [
            'jobs' => (clone $jobs)->count(),
            'events' => (clone $events)->count(),
        ],
    ]);
})->name('home');

Route::get('/health', fn () => response()->json([
    'status' => 'ok',
    'core_identity' => config('core_identity.http.enabled') ? 'configured' : 'unavailable',
]))->name('health');

Route::get('/event-flyers/{event:slug}', [EventFlyerController::class, 'show'])
    ->middleware('throttle:60,1')->name('event-flyers.show');

Route::get('/login', fn () => Inertia::render('Login', [
    'coreRecoveryUrl' => config('core_identity.recovery_url'),
]))->name('login');
Route::post('/internal/session', [InternalSessionController::class, 'store'])
    ->middleware('throttle:10,1')->name('internal-session.store');
Route::delete('/internal/session', [InternalSessionController::class, 'destroy'])
    ->name('internal-session.destroy');

Route::prefix('company')->name('company.')->group(function () {
    Route::get('/register', [CompanyAuthController::class, 'create'])->name('register');
    Route::post('/register', [CompanyAuthController::class, 'store'])->middleware('throttle:4,1')->name('register.store');
    Route::get('/login', [CompanyAuthController::class, 'login'])->name('login');
    Route::post('/login', [CompanyAuthController::class, 'authenticate'])->middleware('throttle:8,1')->name('login.store');
    Route::middleware('company.auth')->group(function () {
        Route::delete('/session', [CompanyAuthController::class, 'destroy'])->name('logout');
        Route::get('/dashboard', CompanyDashboardController::class)->name('dashboard');
        Route::get('/notifications', [NotificationController::class, 'company'])->name('notifications.index');
        Route::put('/notifications/{reference}/read', [NotificationController::class, 'readCompany'])->name('notifications.read');
        Route::get('/recruiters', [CompanyRecruiterController::class, 'index'])->name('recruiters.index');
        Route::post('/recruiters', [CompanyRecruiterController::class, 'store'])->middleware('throttle:8,1')->name('recruiters.store');
        Route::put('/recruiters/{reference}', [CompanyRecruiterController::class, 'update'])->name('recruiters.update');
        Route::middleware(['company.verified', 'throttle:40,1'])->group(function () {
            Route::get('/jobs', [App\Http\Controllers\Company\JobController::class, 'index'])->name('jobs.index');
            Route::get('/jobs/create', [App\Http\Controllers\Company\JobController::class, 'create'])->name('jobs.create');
            Route::post('/jobs', [App\Http\Controllers\Company\JobController::class, 'store'])->name('jobs.store');
            Route::get('/jobs/{reference}/edit', [App\Http\Controllers\Company\JobController::class, 'edit'])->name('jobs.edit');
            Route::put('/jobs/{reference}', [App\Http\Controllers\Company\JobController::class, 'update'])->name('jobs.update');
            Route::put('/jobs/{reference}/close', [App\Http\Controllers\Company\JobController::class, 'close'])->name('jobs.close');
            Route::get('/jobs/{reference}/applicants', [JobApplicantController::class, 'index'])->name('jobs.applicants');
            Route::put('/jobs/{jobReference}/applicants/{applicationReference}', [JobApplicantController::class, 'update'])->name('jobs.applicants.update');
            Route::get('/applications/{reference}/documents/{document}', [ApplicationDocumentController::class, 'companyDownload'])->name('applications.documents.show');
            Route::post('/applications/{applicationReference}/feedback', [EmployerFeedbackController::class, 'store'])->name('applications.feedback.store');
            Route::post('/jobs/{jobReference}/invite/{talentReference}', [App\Http\Controllers\Company\JobInvitationController::class, 'store'])->name('jobs.invite');
            Route::get('/talent', [TalentSearchController::class, 'company'])->name('talent.index');
            Route::get('/talent/{reference}', [TalentProfileController::class, 'company'])->name('talent.show');
            Route::post('/talent/{reference}/shortlist', [TalentSearchController::class, 'shortlist'])->name('talent.shortlist');
            Route::delete('/talent/{reference}/shortlist', [TalentSearchController::class, 'unshortlist'])->name('talent.unshortlist');
        });
    });
});

Route::get('/register', [AlumniRegistrationController::class, 'create'])->name('register');
Route::post('/register', [AlumniRegistrationController::class, 'store'])
    ->middleware('throttle:6,1')->name('register.store');
Route::get('/status', [RegistrationStatusController::class, 'index'])->name('registration-status.index');
Route::get('/status/{reference}', [RegistrationStatusController::class, 'show'])
    ->middleware('throttle:30,1')->name('registration-status.show');

Route::prefix('s')->name('public-cv.')->middleware('throttle:60,1')->group(function () {
    Route::get('/{token}', [PublicCvController::class, 'show'])->name('show');
    Route::get('/{token}/photo', [PublicCvController::class, 'photo'])->name('photo');
    Route::get('/{token}/download.pdf', [CareerCvExportController::class, 'publicPdf'])->middleware('throttle:12,1')->name('pdf');
});

Route::get('/dashboard', CandidateDashboardController::class)
    ->middleware(['core.principal', 'career.can:'.CareerCapability::CandidateDashboardView->value])
    ->name('dashboard');

Route::prefix('alumni')->name('alumni.')->middleware([
    'core.principal', 'career.can:'.CareerCapability::CandidateDashboardView->value,
])->group(function () {
    Route::get('/', [AlumniDirectoryController::class, 'index'])->name('index');
    Route::get('/{profile:talent_reference}/photo', [AlumniDirectoryController::class, 'photo'])->name('photo');
});

Route::get('/notifications', [NotificationController::class, 'candidate'])
    ->middleware(['core.principal', 'career.can:'.CareerCapability::NotificationReadOwn->value])->name('notifications.index');
Route::put('/notifications/{reference}/read', [NotificationController::class, 'readCandidate'])
    ->middleware(['core.principal', 'career.can:'.CareerCapability::NotificationReadOwn->value])->name('notifications.read');

Route::prefix('tracer')->name('tracer.')->middleware(['core.principal', 'career.can:'.CareerCapability::TracerSubmitOwn->value])->group(function () {
    Route::get('/', [TracerController::class, 'index'])->name('index');
    Route::get('/{reference}', [TracerController::class, 'show'])->name('show');
    Route::put('/{reference}', [TracerController::class, 'save'])->name('save');
});

Route::prefix('jobs')->name('jobs.')->middleware('core.principal')->group(function () {
    Route::get('/', [JobController::class, 'index'])->middleware('career.can:'.CareerCapability::JobBrowse->value)->name('index');
    Route::get('/applications', [JobApplicationController::class, 'index'])->middleware('career.can:'.CareerCapability::JobApplyOwn->value)->name('applications.index');
    Route::post('/applications/{reference}/documents', [ApplicationDocumentController::class, 'store'])->middleware('career.can:'.CareerCapability::JobApplyOwn->value)->name('applications.documents.store');
    Route::get('/applications/{reference}/documents/{document}', [ApplicationDocumentController::class, 'candidateDownload'])->middleware('career.can:'.CareerCapability::JobApplyOwn->value)->name('applications.documents.show');
    Route::get('/invitations', [JobInvitationController::class, 'index'])->middleware('career.can:'.CareerCapability::JobInvitationOwn->value)->name('invitations.index');
    Route::put('/invitations/{reference}', [JobInvitationController::class, 'respond'])->middleware('career.can:'.CareerCapability::JobInvitationOwn->value)->name('invitations.respond');
    Route::post('/{reference}/apply', [JobApplicationController::class, 'store'])->middleware(['career.can:'.CareerCapability::JobApplyOwn->value, 'throttle:12,1'])->name('apply');
    Route::post('/{reference}/external-opened', [JobController::class, 'external'])->middleware(['career.can:'.CareerCapability::JobApplyOwn->value, 'throttle:20,1'])->name('external');
    Route::post('/{reference}/self-report', [JobApplicationController::class, 'selfReport'])->middleware('career.can:'.CareerCapability::JobApplyOwn->value)->name('self-report');
    Route::delete('/applications/{reference}', [JobApplicationController::class, 'withdraw'])->middleware('career.can:'.CareerCapability::JobApplyOwn->value)->name('applications.withdraw');
    Route::post('/{reference}/bookmark', [JobController::class, 'bookmark'])->middleware('career.can:'.CareerCapability::JobApplyOwn->value)->name('bookmark');
    Route::delete('/{reference}/bookmark', [JobController::class, 'unbookmark'])->middleware('career.can:'.CareerCapability::JobApplyOwn->value)->name('unbookmark');
    Route::post('/{reference}/report', [JobController::class, 'report'])->middleware(['career.can:'.CareerCapability::JobApplyOwn->value, 'throttle:8,1'])->name('report');
    Route::get('/{reference}', [JobController::class, 'show'])->middleware('career.can:'.CareerCapability::JobBrowse->value)->name('show');
});

Route::prefix('events')->name('events.')->middleware('core.principal')->group(function () {
    Route::get('/', [EventController::class, 'index'])->middleware('career.can:'.CareerCapability::EventViewPublished->value)->name('index');
    Route::get('/mine', [EventController::class, 'mine'])->middleware('career.can:'.CareerCapability::EventHistoryOwn->value)->name('mine');
    Route::get('/certificates/{certificate}', [EventCertificateController::class, 'show'])->middleware('career.can:'.CareerCapability::CertificateViewOwn->value)->name('certificate');
    Route::get('/{event:slug}', [EventController::class, 'show'])->middleware('career.can:'.CareerCapability::EventViewPublished->value)->name('show');
    Route::post('/{event:slug}/registrations', [EventRegistrationController::class, 'store'])->middleware('career.can:'.CareerCapability::EventRegisterOwn->value)->name('register');
    Route::delete('/{event:slug}/registrations/{registration}', [EventRegistrationController::class, 'destroy'])->middleware('career.can:'.CareerCapability::EventRegisterOwn->value)->name('cancel');
});

Route::prefix('cv')->name('cv.')->middleware('core.principal')->group(function () {
    Route::get('/', [CareerCvController::class, 'index'])->middleware('career.can:'.CareerCapability::CvViewOwn->value)->name('index');
    Route::get('/create', [CareerCvController::class, 'create'])->middleware('career.can:'.CareerCapability::CvManageOwn->value)->name('create');
    Route::post('/', [CareerCvController::class, 'store'])->middleware('career.can:'.CareerCapability::CvManageOwn->value)->name('store');
    Route::get('/{cv}/edit', [CareerCvController::class, 'edit'])->middleware('career.can:'.CareerCapability::CvManageOwn->value)->name('edit');
    Route::put('/{cv}', [CareerCvController::class, 'update'])->middleware('career.can:'.CareerCapability::CvManageOwn->value)->name('update');
    Route::delete('/{cv}', [CareerCvController::class, 'destroy'])->middleware('career.can:'.CareerCapability::CvManageOwn->value)->name('destroy');
    Route::post('/{cv}/duplicate', [CareerCvDuplicateController::class, 'store'])->middleware('career.can:'.CareerCapability::CvManageOwn->value)->name('duplicate');
    Route::get('/{cv}/preview', [CareerCvPreviewController::class, 'show'])->middleware('career.can:'.CareerCapability::CvViewOwn->value)->name('preview');
    Route::post('/{cv}/publish', [CareerCvPublishController::class, 'store'])->middleware('career.can:'.CareerCapability::CvManageOwn->value)->name('publish');
    Route::get('/{cv}/download.pdf', [CareerCvExportController::class, 'pdf'])->middleware(['career.can:'.CareerCapability::CvViewOwn->value, 'throttle:12,1'])->name('pdf');
    Route::get('/{cv}/download.docx', [CareerCvExportController::class, 'docx'])->middleware(['career.can:'.CareerCapability::CvViewOwn->value, 'throttle:12,1'])->name('docx');
    Route::post('/{cv}/shares', [CvShareLinkController::class, 'store'])->middleware('career.can:'.CareerCapability::CvManageOwn->value)->name('shares.store');
    Route::put('/{cv}/shares/{share}', [CvShareLinkController::class, 'update'])->middleware('career.can:'.CareerCapability::CvManageOwn->value)->name('shares.update');
    Route::post('/{cv}/shares/{share}/rotate', [CvShareLinkController::class, 'rotate'])->middleware('career.can:'.CareerCapability::CvManageOwn->value)->name('shares.rotate');
    Route::delete('/{cv}/shares/{share}', [CvShareLinkController::class, 'destroy'])->middleware('career.can:'.CareerCapability::CvManageOwn->value)->name('shares.destroy');
});

Route::get('/staff', StaffOverviewController::class)
    ->middleware(['core.principal', 'career.can:'.CareerCapability::AggregateDashboardView->value])
    ->name('staff.overview');
Route::get('/staff/exports/{format}', [OperationalExportController::class, 'download'])
    ->middleware(['core.principal', 'career.can:'.CareerCapability::OperationalExport->value, 'throttle:12,1'])->name('staff.exports.download');

Route::prefix('profile')->name('profile.')->middleware('core.principal')->group(function () {
    Route::get('/', [ProfileController::class, 'index'])
        ->middleware('career.can:'.CareerCapability::ProfileViewOwn->value)->name('index');
    Route::get('/edit', [ProfileController::class, 'edit'])
        ->middleware('career.can:'.CareerCapability::ProfileViewOwn->value)->name('edit');
    Route::put('/', [ProfileController::class, 'update'])
        ->middleware('career.can:'.CareerCapability::ProfileUpdateOwn->value)->name('update');
    Route::put('/discoverability', [CandidateDiscoverabilityController::class, 'update'])
        ->middleware('career.can:'.CareerCapability::ProfileUpdateOwn->value)->name('discoverability.update');
    Route::post('/confirm', [ProfileController::class, 'confirm'])
        ->middleware('career.can:'.CareerCapability::ProfileUpdateOwn->value)->name('confirm');
    Route::get('/photo', [ProfilePhotoController::class, 'show'])
        ->middleware('career.can:'.CareerCapability::ProfileViewOwn->value)->name('photo.show');
    Route::post('/photo', [ProfilePhotoController::class, 'store'])
        ->middleware('career.can:'.CareerCapability::ProfileUpdateOwn->value)->name('photo.store');
    Route::delete('/photo', [ProfilePhotoController::class, 'destroy'])
        ->middleware('career.can:'.CareerCapability::ProfileUpdateOwn->value)->name('photo.destroy');
    Route::get('/files/{section}/{record}', [ProfileFileController::class, 'show'])
        ->middleware('career.can:'.CareerCapability::ProfileViewOwn->value)->name('files.show');
    Route::get('/sections/{section}', [ProfileSectionController::class, 'index'])
        ->middleware('career.can:'.CareerCapability::ProfileViewOwn->value)->name('sections.index');
    Route::post('/sections/{section}', [ProfileSectionController::class, 'store'])
        ->middleware('career.can:'.CareerCapability::ProfileUpdateOwn->value)->name('sections.store');
    Route::post('/sections/{section}/{record}', [ProfileSectionController::class, 'update'])
        ->middleware('career.can:'.CareerCapability::ProfileUpdateOwn->value)->name('sections.update');
    Route::delete('/sections/{section}/{record}', [ProfileSectionController::class, 'destroy'])
        ->middleware('career.can:'.CareerCapability::ProfileUpdateOwn->value)->name('sections.destroy');
});

Route::prefix('admin')->name('admin.')->middleware('core.principal')->group(function () {
    Route::prefix('tracer')->name('tracer.')->middleware('career.can:'.CareerCapability::TracerManage->value)->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\TracerController::class, 'index'])->name('index');
        Route::post('/periods', [App\Http\Controllers\Admin\TracerController::class, 'storePeriod'])->name('periods.store');
        Route::post('/periods/{reference}/versions', [App\Http\Controllers\Admin\TracerController::class, 'storeVersion'])->name('versions.store');
        Route::put('/submissions/{reference}/reopen', [App\Http\Controllers\Admin\TracerController::class, 'reopen'])->name('submissions.reopen');
    });
    Route::prefix('jobs')->name('jobs.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\JobController::class, 'index'])->middleware('career.can:'.CareerCapability::JobReview->value)->name('index');
        Route::get('/import', [JobImportController::class, 'index'])->middleware('career.can:'.CareerCapability::JobManage->value)->name('import.index');
        Route::post('/import/preview', [JobImportController::class, 'preview'])->middleware('career.can:'.CareerCapability::JobManage->value)->name('import.preview');
        Route::post('/import/{reference}', [JobImportController::class, 'store'])->middleware('career.can:'.CareerCapability::JobManage->value)->name('import.store');
        Route::get('/applications/{reference}/documents/{document}', [ApplicationDocumentController::class, 'adminDownload'])->middleware('career.can:'.CareerCapability::ApplicationOperationalView->value)->name('applications.documents.show');
        Route::get('/create', [App\Http\Controllers\Admin\JobController::class, 'create'])->middleware('career.can:'.CareerCapability::JobManage->value)->name('create');
        Route::post('/', [App\Http\Controllers\Admin\JobController::class, 'store'])->middleware('career.can:'.CareerCapability::JobManage->value)->name('store');
        Route::get('/{reference}/edit', [App\Http\Controllers\Admin\JobController::class, 'edit'])->middleware('career.can:'.CareerCapability::JobManage->value)->name('edit');
        Route::put('/{reference}', [App\Http\Controllers\Admin\JobController::class, 'update'])->middleware('career.can:'.CareerCapability::JobManage->value)->name('update');
        Route::put('/{reference}/status', [App\Http\Controllers\Admin\JobController::class, 'status'])->middleware('career.can:'.CareerCapability::JobReview->value)->name('status');
        Route::get('/{reference}/source-attachment', [App\Http\Controllers\Admin\JobController::class, 'sourceAttachment'])->middleware('career.can:'.CareerCapability::JobReview->value)->name('source-attachment');
    });
    Route::get('/companies', [CompanyController::class, 'index'])
        ->middleware('career.can:'.CareerCapability::CompanyManage->value)->name('companies.index');
    Route::put('/companies/{reference}', [CompanyController::class, 'update'])
        ->middleware('career.can:'.CareerCapability::CompanyManage->value)->name('companies.update');
    Route::prefix('events')->name('events.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\EventController::class, 'index'])->middleware('career.can:'.CareerCapability::EventAggregateView->value)->name('index');
        Route::get('/create', [App\Http\Controllers\Admin\EventController::class, 'create'])->middleware('career.can:'.CareerCapability::EventManage->value)->name('create');
        Route::post('/', [App\Http\Controllers\Admin\EventController::class, 'store'])->middleware('career.can:'.CareerCapability::EventManage->value)->name('store');
        Route::get('/{event}/edit', [App\Http\Controllers\Admin\EventController::class, 'edit'])->middleware('career.can:'.CareerCapability::EventManage->value)->name('edit');
        Route::get('/{event}/flyer', [EventFlyerController::class, 'admin'])->middleware('career.can:'.CareerCapability::EventManage->value)->name('flyer');
        Route::put('/{event}', [App\Http\Controllers\Admin\EventController::class, 'update'])->middleware('career.can:'.CareerCapability::EventManage->value)->name('update');
        Route::put('/{event}/status', [App\Http\Controllers\Admin\EventController::class, 'status'])->middleware('career.can:'.CareerCapability::EventManage->value)->name('status');
        Route::get('/{event}/participants', [EventParticipantController::class, 'index'])->middleware('career.can:'.CareerCapability::EventParticipantManage->value)->name('participants');
        Route::put('/{event}/participants/{registration}', [EventParticipantController::class, 'update'])->middleware('career.can:'.CareerCapability::EventParticipantManage->value)->name('participants.update');
        Route::post('/registrations/{registration}/certificate', [App\Http\Controllers\Admin\EventCertificateController::class, 'store'])->middleware('career.can:'.CareerCapability::CertificateManage->value)->name('certificates.store');
        Route::delete('/certificates/{certificate}', [App\Http\Controllers\Admin\EventCertificateController::class, 'destroy'])->middleware('career.can:'.CareerCapability::CertificateManage->value)->name('certificates.destroy');
    });
    Route::prefix('cv-templates')->name('cv-templates.')
        ->middleware('career.can:'.CareerCapability::TemplateManage->value)->group(function () {
            Route::get('/', [CvTemplateController::class, 'index'])->name('index');
            Route::post('/', [CvTemplateController::class, 'store'])->name('store');
            Route::put('/order', [CvTemplateController::class, 'reorder'])->name('reorder');
            Route::get('/{template}', [CvTemplateController::class, 'show'])->name('show');
            Route::put('/{template}', [CvTemplateController::class, 'update'])->name('update');
            Route::post('/{template}/duplicate', [CvTemplateController::class, 'duplicate'])->name('duplicate');
            Route::post('/{template}/publish', [CvTemplateController::class, 'publish'])->name('publish');
            Route::post('/{template}/retire', [CvTemplateController::class, 'retire'])->name('retire');
            Route::post('/{template}/reactivate', [CvTemplateController::class, 'reactivate'])->name('reactivate');
            Route::get('/{template}/preview', [CvTemplateController::class, 'preview'])->name('preview');
        });
    Route::get('/registrations', [AdminRegistrationController::class, 'index'])
        ->middleware('career.can:'.CareerCapability::RegistrationQueueView->value)
        ->name('registrations.index');
    Route::get('/registrations/{reference}', [AdminRegistrationController::class, 'show'])
        ->middleware('career.can:'.CareerCapability::RegistrationDetailView->value)
        ->name('registrations.show');
    Route::post('/registrations/{reference}/approve', [RegistrationDecisionController::class, 'approve'])
        ->middleware('career.can:'.CareerCapability::RegistrationApprove->value)
        ->name('registrations.approve');
    Route::post('/registrations/{reference}/reject', [RegistrationDecisionController::class, 'reject'])
        ->middleware('career.can:'.CareerCapability::RegistrationReject->value)
        ->name('registrations.reject');
});

Route::prefix('internal/talent')->name('internal.talent.')->middleware([
    'core.principal', 'career.can:'.CareerCapability::TalentDirectorySearchInternal->value, 'throttle:40,1',
])->group(function () {
    Route::get('/', [TalentSearchController::class, 'internal'])->name('index');
    Route::get('/{reference}', [TalentProfileController::class, 'internal'])->name('show');
});
