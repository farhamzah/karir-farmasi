<?php

namespace App\Jobs;

use App\Cv\CareerCvProjection;
use App\Models\CareerCv;
use App\Models\CareerJob;
use App\Models\CareerJobApplication;
use App\Models\CareerProfile;
use App\Operational\InAppNotification;
use Illuminate\Support\Facades\DB;

final class ApplicationWorkflow
{
    public function __construct(
        private readonly CareerCvProjection $projection,
        private readonly JobAuditRecorder $audit,
        private readonly InAppNotification $notifications,
    ) {}

    public function submit(CareerJob $job, CareerProfile $profile, CareerCv $cv, ?string $coverLetter): CareerJobApplication
    {
        return DB::transaction(function () use ($job, $profile, $cv, $coverLetter): CareerJobApplication {
            if (! $job->isPublishedAndOpen() || $job->application_method !== 'internal') {
                abort(422, 'Lowongan tidak menerima lamaran internal.');
            }
            if ($cv->career_profile_id !== $profile->id) {
                abort(404);
            }

            $snapshot = $this->projection->preview($cv);
            $checksum = hash('sha256', json_encode($snapshot, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            $application = CareerJobApplication::query()->create([
                'career_job_id' => $job->id,
                'career_profile_id' => $profile->id,
                'career_cv_id' => $cv->id,
                'cv_template_version_id' => $cv->cv_template_version_id,
                'method' => 'internal',
                'status' => 'submitted',
                'cv_snapshot' => $snapshot,
                'snapshot_checksum' => $checksum,
                'cover_letter' => $coverLetter,
                'submitted_at' => now(),
            ]);
            $application->transitions()->create([
                'from_status' => null,
                'to_status' => 'submitted',
                'actor_type' => 'candidate',
                'actor_reference' => $profile->talent_reference,
                'created_at' => now(),
            ]);
            $this->audit->record('job.application.submitted', $profile->talent_reference, [
                'job_reference' => $job->public_reference,
                'application_reference' => $application->public_reference,
            ]);

            return $application;
        });
    }

    public function transition(CareerJobApplication $application, string $status, string $actorType, string $actorReference, ?string $note = null): void
    {
        $allowed = [
            'submitted' => ['under_review', 'withdrawn', 'rejected', 'cancelled'],
            'under_review' => ['assessment', 'interview', 'rejected', 'withdrawn'],
            'assessment' => ['interview', 'offer', 'rejected', 'withdrawn'],
            'interview' => ['offer', 'rejected', 'withdrawn'],
            'offer' => ['offer_accepted', 'offer_declined', 'withdrawn'],
            'offer_accepted' => ['hired_pending_start'],
            'hired_pending_start' => ['started'],
        ];
        if (! in_array($status, $allowed[$application->status] ?? [], true)) {
            abort(422, 'Transisi status lamaran tidak diizinkan.');
        }

        DB::transaction(function () use ($application, $status, $actorType, $actorReference, $note): void {
            $previous = $application->status;
            $application->transitions()->create([
                'from_status' => $previous,
                'to_status' => $status,
                'actor_type' => $actorType,
                'actor_reference' => $actorReference,
                'note' => $note,
                'created_at' => now(),
            ]);
            $application->update([
                'status' => $status,
                'withdrawn_at' => $status === 'withdrawn' ? now() : $application->withdrawn_at,
            ]);
            $this->audit->record('job.application.status_changed', $actorReference, [
                'application_reference' => $application->public_reference,
                'from' => $previous,
                'to' => $status,
                'actor_type' => $actorType,
            ]);
            $this->notifications->send(
                'candidate',
                $application->profile->core_user_id,
                'application.status_changed',
                'Status lamaran diperbarui',
                "Lamaran {$application->job->title} kini berstatus ".str_replace('_', ' ', $status).'.',
                '/jobs/applications',
                ['application_reference' => $application->public_reference, 'status' => $status],
            );
        });
    }
}
