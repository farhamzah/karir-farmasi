<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Jobs\ApplicationWorkflow;
use App\Models\CareerJob;
use App\Models\CompanyUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class JobApplicantController extends Controller
{
    public function index(Request $request, string $reference): Response
    {
        $job = $this->ownedJob($request, $reference);

        return Inertia::render('Company/Jobs/Applicants', [
            'job' => ['reference' => $job->public_reference, 'title' => $job->title],
            'applications' => $job->applications()->with(['profile:id,professional_name,headline,city', 'documents', 'employerFeedback'])->latest('submitted_at')->get()->map(fn ($application) => [
                'reference' => $application->public_reference,
                'candidate' => $application->profile?->professional_name ?: 'Kandidat',
                'headline' => $application->profile?->headline,
                'city' => $application->profile?->city,
                'status' => $application->status,
                'method' => $application->method,
                'submitted_at' => $application->submitted_at?->toDateString(),
                'cover_letter' => $application->cover_letter,
                'cv_snapshot' => $application->cv_snapshot,
                'documents' => $application->documents->map(fn ($document): array => ['id' => $document->id, 'type' => $document->type, 'name' => $document->original_name])->all(),
                'feedback_submitted' => $application->employerFeedback->isNotEmpty(),
            ])->all(),
        ]);
    }

    public function update(Request $request, string $jobReference, string $applicationReference, ApplicationWorkflow $workflow): RedirectResponse
    {
        $job = $this->ownedJob($request, $jobReference);
        $application = $job->applications()->where('public_reference', $applicationReference)->firstOrFail();
        $data = $request->validate(['status' => ['required', 'in:under_review,assessment,interview,offer,offer_accepted,offer_declined,rejected,cancelled,hired_pending_start,started'], 'note' => ['nullable', 'string', 'max:1000']]);
        $user = $this->user($request);
        $workflow->transition($application, $data['status'], 'company', $user->public_reference, $data['note'] ?? null);

        return back()->with('success', 'Status lamaran diperbarui.');
    }

    private function ownedJob(Request $request, string $reference): CareerJob
    {
        return CareerJob::where('company_id', $this->user($request)->company_id)->where('public_reference', $reference)->firstOrFail();
    }

    private function user(Request $request): CompanyUser
    {
        return $request->attributes->get(CompanyUser::class);
    }
}
