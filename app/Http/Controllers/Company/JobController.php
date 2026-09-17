<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Http\Requests\SaveCareerJobRequest;
use App\Jobs\JobWorkflow;
use App\Models\CareerJob;
use App\Models\CompanyUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class JobController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $this->user($request);

        return Inertia::render('Company/Jobs/Index', [
            'jobs' => CareerJob::with('tags')->withCount('applications')->where('company_id', $user->company_id)->latest()->get()->map(fn ($job) => [
                'reference' => $job->public_reference, 'title' => $job->title, 'status' => $job->status,
                'expires_at' => $job->expires_at?->toDateString(), 'possible_duplicate' => $job->possible_duplicate,
                'applications_count' => $job->applications_count, 'tags' => $job->tags->pluck('label')->all(),
            ])->all(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Company/Jobs/Edit', ['job' => null]);
    }

    public function store(SaveCareerJobRequest $request, JobWorkflow $workflow): RedirectResponse
    {
        return $this->persist($request, new CareerJob, $workflow);
    }

    public function edit(Request $request, string $reference): Response
    {
        $job = $this->owned($request, $reference)->load('tags');

        return Inertia::render('Company/Jobs/Edit', ['job' => $job->toArray() + ['tags_text' => $job->tags->pluck('label')->implode(', ')]]);
    }

    public function update(SaveCareerJobRequest $request, string $reference, JobWorkflow $workflow): RedirectResponse
    {
        return $this->persist($request, $this->owned($request, $reference), $workflow);
    }

    public function close(Request $request, string $reference): RedirectResponse
    {
        $this->owned($request, $reference)->update(['status' => 'closed', 'closed_at' => now()]);

        return back()->with('success', 'Lowongan ditutup.');
    }

    private function persist(SaveCareerJobRequest $request, CareerJob $job, JobWorkflow $workflow): RedirectResponse
    {
        $user = $this->user($request);
        $data = $request->validated();
        $tags = $data['tags'] ?? null;
        unset($data['tags'], $data['source_type'], $data['source_name'], $data['source_reference'], $data['received_at'], $data['source_verified'], $data['internal_notes'], $data['source_attachment']);
        $data['salary_visible'] = (bool) ($data['salary_visible'] ?? false);
        $data['company_id'] = $user->company_id;
        $data['employer_display_name'] = $user->company->display_name;
        $data['created_by_type'] = 'company';
        $data['created_by_reference'] = $user->public_reference;
        $data['source_type'] = 'company_direct';
        $data['source_name'] = $user->company->display_name;
        $data['source_verified_at'] = now();
        $data['status'] = $job->exists ? 'draft' : 'draft';
        $job->fill($data)->save();
        $workflow->syncTags($job, $tags);
        $workflow->flagPossibleDuplicate($job);

        return redirect()->route('company.jobs.index')->with('success', 'Lowongan disimpan sebagai draft untuk review kampus.');
    }

    private function owned(Request $request, string $reference): CareerJob
    {
        return CareerJob::where('company_id', $this->user($request)->company_id)->where('public_reference', $reference)->firstOrFail();
    }

    private function user(Request $request): CompanyUser
    {
        return $request->attributes->get(CompanyUser::class);
    }
}
