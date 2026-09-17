<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\CareerJob;
use App\Models\CareerJobInvitation;
use App\Models\CareerProfile;
use App\Models\CompanyUser;
use App\Operational\InAppNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class JobInvitationController extends Controller
{
    public function store(Request $request, string $jobReference, string $talentReference, InAppNotification $notifications): RedirectResponse
    {
        $data = $request->validate(['message' => ['nullable', 'string', 'max:500'], 'expires_at' => ['nullable', 'date', 'after:today']]);
        /** @var CompanyUser $user */
        $user = $request->attributes->get(CompanyUser::class);
        $job = CareerJob::where('company_id', $user->company_id)->where('public_reference', $jobReference)->firstOrFail();
        abort_unless($job->isPublishedAndOpen(), 422, 'Undangan hanya dapat dibuat untuk lowongan aktif.');
        $profile = CareerProfile::where('talent_reference', $talentReference)->where('discoverable_by_verified_companies', true)->firstOrFail();
        $invitation = CareerJobInvitation::updateOrCreate(
            ['career_job_id' => $job->id, 'career_profile_id' => $profile->id],
            $data + ['company_id' => $user->company_id, 'company_user_id' => $user->id, 'status' => 'sent', 'responded_at' => null],
        );
        $notifications->send('candidate', $profile->core_user_id, 'job.invitation.created', 'Undangan lowongan baru',
            "{$user->company->display_name} mengundang Anda untuk {$job->title}.", '/jobs/invitations', ['invitation_reference' => $invitation->public_reference]);

        return back()->with('success', 'Undangan lowongan dikirim. Kandidat tetap memilih apakah akan melamar.');
    }
}
