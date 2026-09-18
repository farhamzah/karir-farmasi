<?php

namespace App\Http\Controllers\Admin;

use App\Data\CareerActor;
use App\Http\Controllers\Controller;
use App\Http\Requests\SaveCareerJobRequest;
use App\Jobs\JobWorkflow;
use App\Models\CareerJob;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class JobController extends Controller
{
    public function index(JobWorkflow $workflow): Response
    {
        $workflow->expireDueJobs();

        return Inertia::render('Admin/Jobs/Index', ['jobs' => CareerJob::with('tags')->withCount(['applications', 'reports'])->latest()->get()->map(fn ($job) => [
            'reference' => $job->public_reference, 'title' => $job->title, 'employer' => $job->employer_display_name,
            'source' => $job->source_name, 'status' => $job->status, 'expires_at' => $job->expires_at?->toDateString(),
            'possible_duplicate' => $job->possible_duplicate, 'applications_count' => $job->applications_count,
            'reports_count' => $job->reports_count, 'tags' => $job->tags->pluck('label')->all(), 'has_flyer' => $job->flyer_path !== null,
        ])->all()]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Jobs/Edit', ['job' => null]);
    }

    public function store(SaveCareerJobRequest $request, JobWorkflow $workflow): RedirectResponse
    {
        return $this->persist($request, new CareerJob, $workflow);
    }

    public function edit(string $reference): Response
    {
        $job = CareerJob::with('tags')->where('public_reference', $reference)->firstOrFail();

        return Inertia::render('Admin/Jobs/Edit', ['job' => $job->toArray() + [
            'tags_text' => $job->tags->pluck('label')->implode(', '),
            'has_flyer' => $job->flyer_path !== null,
            'flyer_url' => $job->flyer_path ? route('admin.jobs.flyer', $job->public_reference) : null,
        ]]);
    }

    public function update(SaveCareerJobRequest $request, string $reference, JobWorkflow $workflow): RedirectResponse
    {
        return $this->persist($request, CareerJob::where('public_reference', $reference)->firstOrFail(), $workflow);
    }

    public function status(Request $request, string $reference, JobWorkflow $workflow): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', 'in:published,closed,rejected'], 'note' => ['nullable', 'string', 'max:1000']]);
        $job = CareerJob::where('public_reference', $reference)->firstOrFail();
        /** @var CareerActor $actor */ $actor = $request->attributes->get(CareerActor::class);
        if ($data['status'] === 'published') {
            $workflow->publish($job, $actor->coreUserId, $data['note'] ?? null);
        } else {
            $job->update(['status' => $data['status'], 'closed_at' => now(), 'reviewed_by' => $actor->coreUserId, 'review_note' => $data['note'] ?? null]);
        }

        return back()->with('success', 'Status lowongan diperbarui.');
    }

    public function sourceAttachment(string $reference): StreamedResponse
    {
        $job = CareerJob::where('public_reference', $reference)->firstOrFail();
        abort_if(! $job->source_attachment_path || ! Storage::disk('career_private')->exists($job->source_attachment_path), 404);

        return Storage::disk('career_private')->download($job->source_attachment_path, 'bukti-sumber-'.str($job->title)->slug().'.'.pathinfo($job->source_attachment_path, PATHINFO_EXTENSION));
    }

    private function persist(SaveCareerJobRequest $request, CareerJob $job, JobWorkflow $workflow): RedirectResponse
    {
        $data = $request->validated();
        if (blank($data['source_type'] ?? null) || blank($data['source_name'] ?? null)) {
            throw ValidationException::withMessages(['source_name' => 'Jenis dan nama sumber wajib untuk lowongan kampus.']);
        }
        $tags = $data['tags'] ?? null;
        $sourceVerified = (bool) ($data['source_verified'] ?? false);
        unset($data['tags'], $data['source_attachment'], $data['flyer'], $data['source_verified']);
        if (blank($data['description'] ?? null) && ! $request->hasFile('flyer') && blank($job->flyer_path)) {
            throw ValidationException::withMessages([
                'description' => 'Isi keterangan lowongan atau unggah flyer.',
                'flyer' => 'Unggah flyer jika keterangan lowongan dikosongkan.',
            ]);
        }
        /** @var CareerActor $actor */ $actor = $request->attributes->get(CareerActor::class);
        if (! $job->exists) {
            $data['company_id'] = null;
            $data['created_by_type'] = 'campus';
            $data['created_by_reference'] = $actor->coreUserId;
            $data['status'] = 'draft';
        }
        $data['source_verified_at'] = $sourceVerified ? now() : null;
        $data['source_verified_by'] = $sourceVerified ? $actor->coreUserId : null;
        if ($request->hasFile('source_attachment')) {
            if ($job->source_attachment_path) {
                Storage::disk('career_private')->delete($job->source_attachment_path);
            }
            $data['source_attachment_path'] = $request->file('source_attachment')->store('jobs/source-evidence', 'career_private');
        }
        if ($request->hasFile('flyer')) {
            if ($job->flyer_path) {
                Storage::disk('career_private')->delete($job->flyer_path);
            }
            $data['flyer_path'] = $request->file('flyer')->store('jobs/flyers', 'career_private');
            $data['flyer_alt_text'] = $data['flyer_alt_text'] ?: 'Flyer lowongan '.$data['title'].' dari '.$data['employer_display_name'];
        }
        if (blank($data['description'] ?? null)) {
            $data['description'] = 'Informasi lengkap tersedia pada flyer lowongan.';
        }
        $data['salary_visible'] = (bool) ($data['salary_visible'] ?? false);
        $data['status'] = 'draft';
        $job->fill($data)->save();
        $workflow->syncTags($job, $tags);
        $workflow->flagPossibleDuplicate($job);

        return redirect()->route('admin.jobs.index')->with('success', 'Lowongan kampus disimpan sebagai draft.');
    }
}
