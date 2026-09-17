<?php

namespace App\Http\Controllers;

use App\Data\CareerActor;
use App\Models\CareerJobInvitation;
use App\Profile\CareerProfileStore;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class JobInvitationController extends Controller
{
    public function index(Request $request, CareerProfileStore $profiles): Response
    {
        /** @var CareerActor $actor */
        $actor = $request->attributes->get(CareerActor::class);
        $profile = $profiles->find($actor);
        $invitations = $profile?->jobInvitations()->with(['job.tags', 'company'])->latest()->get()->map(fn ($invitation) => [
            'reference' => $invitation->public_reference,
            'job_reference' => $invitation->job->public_reference,
            'title' => $invitation->job->title,
            'employer' => $invitation->company->display_name,
            'message' => $invitation->message,
            'status' => $invitation->status,
            'expires_at' => $invitation->expires_at?->toDateString(),
            'tags' => $invitation->job->tags->pluck('label')->all(),
        ])->all() ?? [];

        return Inertia::render('Jobs/Invitations', ['invitations' => $invitations]);
    }

    public function respond(Request $request, string $reference, CareerProfileStore $profiles): RedirectResponse
    {
        $data = $request->validate(['decision' => ['required', 'in:accepted,declined']]);
        /** @var CareerActor $actor */
        $actor = $request->attributes->get(CareerActor::class);
        $profile = $profiles->find($actor);
        abort_if($profile === null, 404);
        $invitation = CareerJobInvitation::query()->where('public_reference', $reference)
            ->where('career_profile_id', $profile->id)->whereIn('status', ['sent', 'viewed'])->firstOrFail();
        abort_if($invitation->expires_at?->isPast(), 422, 'Undangan telah kedaluwarsa.');
        $invitation->update(['status' => $data['decision'], 'responded_at' => now()]);

        return back()->with('success', $data['decision'] === 'accepted'
            ? 'Undangan diterima. Kirim lamaran dari halaman lowongan saat Anda siap.'
            : 'Undangan ditolak.');
    }
}
