<?php

namespace App\Http\Controllers;

use App\Data\CareerActor;
use App\Jobs\ApplicationWorkflow;
use App\Models\CareerCv;
use App\Models\CareerJob;
use App\Models\CareerJobApplication;
use App\Profile\CareerProfileStore;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class JobApplicationController extends Controller
{
    public function index(Request $request, CareerProfileStore $profiles): Response
    {
        $profile = $profiles->find($this->actor($request));
        $applications = $profile?->jobApplications()->with(['job', 'documents'])->latest('submitted_at')->get()->map(fn ($application) => [
            'reference' => $application->public_reference,
            'job_reference' => $application->job->public_reference,
            'title' => $application->job->title,
            'employer' => $application->job->employer_display_name,
            'method' => $application->method,
            'status' => $application->status,
            'submitted_at' => $application->submitted_at?->toDateString(),
            'documents' => $application->documents->map(fn ($document): array => ['id' => $document->id, 'type' => $document->type, 'name' => $document->original_name])->all(),
        ])->all() ?? [];

        return Inertia::render('Jobs/Applications', ['applications' => $applications]);
    }

    public function store(Request $request, string $reference, CareerProfileStore $profiles, ApplicationWorkflow $workflow): RedirectResponse
    {
        $data = $request->validate(['cv_id' => ['required', 'integer'], 'cover_letter' => ['nullable', 'string', 'max:5000']]);
        $profile = $profiles->forWrite($this->actor($request));
        $job = CareerJob::query()->where('public_reference', $reference)->firstOrFail();
        $cv = CareerCv::query()->whereKey($data['cv_id'])->where('career_profile_id', $profile->id)->firstOrFail();
        abort_if($job->applications()->where('career_profile_id', $profile->id)->exists(), 422, 'Anda sudah memiliki lamaran untuk lowongan ini.');
        $workflow->submit($job, $profile, $cv, $data['cover_letter'] ?? null);

        return redirect()->route('jobs.applications.index')->with('success', 'Lamaran terkirim dengan snapshot CV yang terkunci.');
    }

    public function selfReport(Request $request, string $reference, CareerProfileStore $profiles): RedirectResponse
    {
        $job = CareerJob::query()->visibleToCandidates()->where('public_reference', $reference)->firstOrFail();
        abort_unless(in_array($job->application_method, ['external_url', 'email_instruction', 'email'], true), 422);
        $profile = $profiles->forWrite($this->actor($request));
        $application = CareerJobApplication::query()->firstOrCreate([
            'career_job_id' => $job->id,
            'career_profile_id' => $profile->id,
        ], [
            'method' => $job->application_method,
            'status' => 'self_reported',
            'submitted_at' => now(),
        ]);
        if ($application->wasRecentlyCreated) {
            $application->transitions()->create(['to_status' => 'self_reported', 'actor_type' => 'candidate', 'actor_reference' => $profile->talent_reference, 'created_at' => now()]);
        }

        return redirect()->route('jobs.applications.index')->with('success', 'Lamaran eksternal dicatat berdasarkan laporan Anda.');
    }

    public function withdraw(Request $request, string $application, CareerProfileStore $profiles, ApplicationWorkflow $workflow): RedirectResponse
    {
        $profile = $profiles->forWrite($this->actor($request));
        $record = CareerJobApplication::query()->where('public_reference', $application)->where('career_profile_id', $profile->id)->firstOrFail();
        $workflow->transition($record, 'withdrawn', 'candidate', $profile->talent_reference);

        return back()->with('success', 'Lamaran ditarik. Riwayat status tetap tersimpan.');
    }

    private function actor(Request $request): CareerActor
    {
        return $request->attributes->get(CareerActor::class);
    }
}
