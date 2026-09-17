<?php

namespace App\Http\Controllers;

use App\Data\CareerActor;
use App\Models\TracerPeriod;
use App\Models\TracerSubmission;
use App\Operational\TracerWorkflow;
use App\Profile\CareerProfileStore;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TracerController extends Controller
{
    public function index(Request $request, CareerProfileStore $profiles): Response
    {
        $profile = $profiles->forWrite($this->actor($request));
        $periods = TracerPeriod::query()->where('status', 'published')->whereDate('starts_on', '<=', today())->whereDate('ends_on', '>=', today())
            ->with(['versions' => fn ($query) => $query->whereNotNull('published_at')->latest('version'), 'submissions' => fn ($query) => $query->where('career_profile_id', $profile->id)])
            ->latest('starts_on')->get()->map(fn (TracerPeriod $period): array => [
                'reference' => $period->public_reference,
                'title' => $period->title,
                'cohort' => $period->cohort,
                'ends_on' => $period->ends_on->toDateString(),
                'status' => $period->submissions->sortByDesc('id')->first()?->status ?? 'not_started',
            ])->all();

        return Inertia::render('Tracer/Index', ['periods' => $periods]);
    }

    public function show(Request $request, string $reference, CareerProfileStore $profiles): Response
    {
        $profile = $profiles->forWrite($this->actor($request));
        $period = TracerPeriod::query()->where('public_reference', $reference)->where('status', 'published')->firstOrFail();
        abort_unless($period->starts_on->isPast() && $period->ends_on->endOfDay()->isFuture(), 404);
        $reopened = TracerSubmission::query()->where('tracer_period_id', $period->id)
            ->where('career_profile_id', $profile->id)->where('status', 'reopened')->latest('reopened_at')->first();
        $version = $reopened?->questionnaireVersion
            ?? $period->versions()->whereNotNull('published_at')->latest('version')->firstOrFail();
        $prefill = ['professional_name' => $profile->professional_name, 'city' => $profile->city,
            'graduation_year' => $profile->educations()->whereNotNull('end_year')->max('end_year')];
        $submission = $reopened ?? TracerSubmission::query()->firstOrCreate(
            ['tracer_questionnaire_version_id' => $version->id, 'career_profile_id' => $profile->id],
            ['tracer_period_id' => $period->id, 'status' => 'draft', 'profile_prefill' => array_filter($prefill, fn ($value) => $value !== null)],
        );

        return Inertia::render('Tracer/Show', ['period' => ['reference' => $period->public_reference, 'title' => $period->title, 'cohort' => $period->cohort],
            'version' => $version->version, 'questions' => $version->questions, 'submission' => ['status' => $submission->status, 'answers' => $submission->answers ?? [], 'prefill' => $submission->profile_prefill ?? []]]);
    }

    public function save(Request $request, string $reference, CareerProfileStore $profiles, TracerWorkflow $workflow): RedirectResponse
    {
        $data = $request->validate(['answers' => ['present', 'array'], 'action' => ['required', 'in:draft,submit']]);
        $profile = $profiles->forWrite($this->actor($request));
        $period = TracerPeriod::query()->where('public_reference', $reference)->where('status', 'published')->firstOrFail();
        $submission = TracerSubmission::query()->where('tracer_period_id', $period->id)
            ->where('career_profile_id', $profile->id)->where('status', 'reopened')->latest('reopened_at')->first()
            ?? TracerSubmission::query()->where('tracer_questionnaire_version_id', $period->versions()->whereNotNull('published_at')->latest('version')->firstOrFail()->id)
                ->where('career_profile_id', $profile->id)->firstOrFail();
        if ($data['action'] === 'submit') {
            $workflow->submit($submission, $data['answers']);

            return back()->with('success', 'Tracer berhasil dikirim dan snapshot dikunci.');
        }
        $workflow->saveDraft($submission, $data['answers']);

        return back()->with('success', 'Draft tracer tersimpan.');
    }

    private function actor(Request $request): CareerActor
    {
        return $request->attributes->get(CareerActor::class);
    }
}
