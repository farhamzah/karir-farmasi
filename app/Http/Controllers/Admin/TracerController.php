<?php

namespace App\Http\Controllers\Admin;

use App\Data\CareerActor;
use App\Http\Controllers\Controller;
use App\Models\TracerPeriod;
use App\Models\TracerQuestionnaireVersion;
use App\Models\TracerSubmission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class TracerController extends Controller
{
    public function index(): Response
    {
        $periods = TracerPeriod::query()->withCount([
            'submissions',
            'submissions as submitted_count' => fn ($query) => $query->where('status', 'submitted'),
        ])->withMax('versions', 'version')->latest('starts_on')->get();

        return Inertia::render('Admin/Tracer/Index', [
            'periods' => $periods->map(fn ($period): array => [
                'reference' => $period->public_reference,
                'title' => $period->title,
                'cohort' => $period->cohort,
                'program_reference' => $period->program_reference,
                'status' => $period->status,
                'starts_on' => $period->starts_on->toDateString(),
                'ends_on' => $period->ends_on->toDateString(),
                'version' => $period->versions_max_version,
                'responses' => $period->submitted_count,
                'total' => $period->submissions_count,
            ])->all(),
        ]);
    }

    public function storePeriod(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'cohort' => ['required', 'string', 'max:80'],
            'program_reference' => ['nullable', 'string', 'max:120'],
            'faculty_reference' => ['nullable', 'string', 'max:120'],
            'starts_on' => ['required', 'date'],
            'ends_on' => ['required', 'date', 'after_or_equal:starts_on'],
        ]);
        $actor = $this->actor($request);
        TracerPeriod::query()->create($data + ['created_by_core_user_id' => $actor->coreUserId, 'status' => 'draft']);

        return back()->with('success', 'Periode tracer dibuat sebagai draft.');
    }

    public function storeVersion(Request $request, string $reference): RedirectResponse
    {
        $period = TracerPeriod::query()->where('public_reference', $reference)->firstOrFail();
        $data = $request->validate([
            'questions' => ['required', 'array', 'min:1', 'max:30'],
            'questions.*.id' => ['required', 'alpha_dash', 'max:80'],
            'questions.*.label' => ['required', 'string', 'max:255'],
            'questions.*.type' => ['required', Rule::in(['text', 'textarea', 'select', 'number', 'date'])],
            'questions.*.required' => ['required', 'boolean'],
            'questions.*.options' => ['nullable', 'array', 'max:20'],
        ]);
        $version = (int) $period->versions()->max('version') + 1;
        TracerQuestionnaireVersion::query()->create([
            'tracer_period_id' => $period->id,
            'version' => $version,
            'questions' => $data['questions'],
            'published_at' => now(),
            'created_by_core_user_id' => $this->actor($request)->coreUserId,
        ]);
        $period->update(['status' => 'published']);

        return back()->with('success', "Kuesioner versi {$version} dipublikasikan.");
    }

    public function reopen(Request $request, string $reference): RedirectResponse
    {
        $submission = TracerSubmission::query()->where('public_reference', $reference)->where('status', 'submitted')->firstOrFail();
        $submission->update([
            'status' => 'reopened',
            'reopened_at' => now(),
            'reopened_by_core_user_id' => $this->actor($request)->coreUserId,
        ]);

        return back()->with('success', 'Respons dibuka kembali untuk revisi eksplisit.');
    }

    private function actor(Request $request): CareerActor
    {
        return $request->attributes->get(CareerActor::class);
    }
}
