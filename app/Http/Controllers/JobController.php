<?php

namespace App\Http\Controllers;

use App\Data\CareerActor;
use App\Jobs\JobPresenter;
use App\Jobs\JobWorkflow;
use App\Models\CareerJob;
use App\Profile\CareerProfileStore;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class JobController extends Controller
{
    public function index(Request $request, JobWorkflow $workflow, JobPresenter $presenter, CareerProfileStore $profiles): Response
    {
        $workflow->expireDueJobs();
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'city' => ['nullable', 'string', 'max:120'],
            'work_mode' => ['nullable', 'in:onsite,hybrid,remote'],
            'employment_type' => ['nullable', 'in:full_time,part_time,contract,internship,project,temporary'],
            'tag' => ['nullable', 'string', 'max:100'],
            'employer' => ['nullable', 'string', 'max:255'],
        ]);
        $query = CareerJob::query()->visibleToCandidates()->with('tags');
        $query->when($filters['q'] ?? null, fn ($q, $term) => $q->where(fn ($inner) => $inner
            ->where('title', 'like', "%{$term}%")->orWhere('employer_display_name', 'like', "%{$term}%")
            ->orWhere('description', 'like', "%{$term}%")->orWhereHas('tags', fn ($tags) => $tags->where('label', 'like', "%{$term}%"))));
        foreach (['city', 'work_mode', 'employment_type'] as $field) {
            $query->when($filters[$field] ?? null, fn ($q, $value) => $q->where($field, $value));
        }
        $query->when($filters['employer'] ?? null, fn ($q, $value) => $q->where('employer_display_name', 'like', "%{$value}%"));
        $query->when($filters['tag'] ?? null, fn ($q, $tag) => $q->whereHas('tags', fn ($tags) => $tags->where('normalized_label', 'like', '%'.str($tag)->lower()->ascii()->squish().'%')));

        /** @var CareerActor $actor */
        $actor = $request->attributes->get(CareerActor::class);
        $profile = $profiles->find($actor);
        $bookmarks = $profile ? $profile->jobBookmarks()->pluck('career_job_id')->all() : [];

        return Inertia::render('Jobs/Index', [
            'filters' => $filters,
            'jobs' => $query->latest('published_at')->get()->map(fn ($job) => $presenter->card($job) + ['bookmarked' => in_array($job->id, $bookmarks, true)])->all(),
        ]);
    }

    public function show(Request $request, string $reference, JobPresenter $presenter, CareerProfileStore $profiles): Response
    {
        $job = CareerJob::query()->visibleToCandidates()->with('tags')->where('public_reference', $reference)->firstOrFail();
        /** @var CareerActor $actor */ $actor = $request->attributes->get(CareerActor::class);
        $profile = $profiles->find($actor);

        return Inertia::render('Jobs/Show', [
            'job' => $presenter->detail($job),
            'cvs' => $profile?->cvs()->with('templateVersion.template')->get()->map(fn ($cv) => ['id' => $cv->id, 'name' => $cv->name, 'template' => $cv->templateVersion?->template?->name])->all() ?? [],
            'application' => $profile ? $job->applications()->where('career_profile_id', $profile->id)->first(['public_reference', 'status', 'method']) : null,
        ]);
    }

    public function bookmark(Request $request, string $reference, CareerProfileStore $profiles): RedirectResponse
    {
        $profile = $profiles->forWrite($this->actor($request));
        $job = CareerJob::query()->visibleToCandidates()->where('public_reference', $reference)->firstOrFail();
        $profile->jobBookmarks()->firstOrCreate(['career_job_id' => $job->id]);

        return back()->with('success', 'Lowongan disimpan.');
    }

    public function unbookmark(Request $request, string $reference, CareerProfileStore $profiles): RedirectResponse
    {
        $profile = $profiles->forWrite($this->actor($request));
        $job = CareerJob::query()->where('public_reference', $reference)->firstOrFail();
        $profile->jobBookmarks()->where('career_job_id', $job->id)->delete();

        return back()->with('success', 'Lowongan dihapus dari simpanan.');
    }

    public function external(Request $request, string $reference, CareerProfileStore $profiles): RedirectResponse
    {
        $job = CareerJob::query()->visibleToCandidates()->where('public_reference', $reference)->firstOrFail();
        abort_unless(in_array($job->application_method, ['external_url', 'email_instruction', 'email'], true), 422);
        $profile = $profiles->forWrite($this->actor($request));
        $job->externalActions()->create(['career_profile_id' => $profile->id, 'action' => 'opened', 'occurred_at' => now()]);

        return back()->with('success', 'Tautan dibuka. Ini belum tercatat sebagai lamaran terkirim.');
    }

    public function report(Request $request, string $reference, CareerProfileStore $profiles): RedirectResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:100'], 'detail' => ['nullable', 'string', 'max:1000']]);
        $job = CareerJob::query()->visibleToCandidates()->where('public_reference', $reference)->firstOrFail();
        $profile = $profiles->forWrite($this->actor($request));
        $job->reports()->updateOrCreate(['career_profile_id' => $profile->id], $data + ['status' => 'open']);

        return back()->with('success', 'Laporan lowongan diterima untuk ditinjau.');
    }

    private function actor(Request $request): CareerActor
    {
        return $request->attributes->get(CareerActor::class);
    }
}
