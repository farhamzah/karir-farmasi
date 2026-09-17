<?php

namespace App\Http\Controllers\Admin;

use App\Data\CareerActor;
use App\Http\Controllers\Controller;
use App\Jobs\JobWorkflow;
use App\Models\CareerJob;
use App\Models\CareerJobImportBatch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class JobImportController extends Controller
{
    public function index(Request $request): Response
    {
        $actor = $this->actor($request);
        $batches = CareerJobImportBatch::query()->where('actor_core_user_id', $actor->coreUserId)->with('rows')->latest()->limit(10)->get();

        return Inertia::render('Admin/Jobs/Import', ['batches' => $batches->map(fn ($batch): array => [
            'reference' => $batch->public_reference,
            'filename' => $batch->original_name,
            'status' => $batch->status,
            'valid_count' => $batch->valid_count,
            'invalid_count' => $batch->invalid_count,
            'duplicate_count' => $batch->duplicate_count,
            'rows' => $batch->rows->map(fn ($row): array => ['row' => $row->row_number, 'payload' => $row->payload, 'errors' => $row->errors, 'duplicate' => $row->possible_duplicate_job_id !== null])->all(),
        ])->all()]);
    }

    public function preview(Request $request): RedirectResponse
    {
        $request->validate(['file' => ['required', 'file', 'mimes:csv,txt', 'max:2048']]);
        $handle = fopen($request->file('file')->getRealPath(), 'rb');
        abort_unless($handle !== false, 422, 'CSV tidak dapat dibaca.');
        $header = array_map(fn ($value): string => Str::snake(trim((string) $value)), fgetcsv($handle) ?: []);
        $required = ['employer_display_name', 'title', 'employment_type', 'work_mode', 'description', 'application_method', 'source_name'];
        abort_unless(array_diff($required, $header) === [], 422, 'Header CSV wajib belum lengkap.');
        $batch = CareerJobImportBatch::query()->create(['actor_core_user_id' => $this->actor($request)->coreUserId, 'original_name' => $request->file('file')->getClientOriginalName()]);
        $valid = $invalid = $duplicates = 0;
        $rowNumber = 1;
        while (($values = fgetcsv($handle)) !== false && $rowNumber < 1001) {
            $rowNumber++;
            $values = array_pad($values, count($header), null);
            $payload = array_combine($header, array_slice($values, 0, count($header))) ?: [];
            $validator = Validator::make($payload, $this->rules());
            $duplicate = CareerJob::query()->where('employer_display_name', trim((string) ($payload['employer_display_name'] ?? '')))
                ->where('title', trim((string) ($payload['title'] ?? '')))->latest()->first();
            $errors = $validator->errors()->toArray();
            $batch->rows()->create(['row_number' => $rowNumber, 'payload' => $payload, 'errors' => $errors ?: null, 'possible_duplicate_job_id' => $duplicate?->id]);
            $errors ? $invalid++ : $valid++;
            $duplicates += $duplicate ? 1 : 0;
        }
        fclose($handle);
        $batch->update(['valid_count' => $valid, 'invalid_count' => $invalid, 'duplicate_count' => $duplicates]);

        return back()->with('success', 'Preview CSV siap ditinjau. Tidak ada lowongan yang dipublikasikan.');
    }

    public function store(Request $request, string $reference, JobWorkflow $workflow): RedirectResponse
    {
        $batch = CareerJobImportBatch::query()->where('public_reference', $reference)->where('actor_core_user_id', $this->actor($request)->coreUserId)->where('status', 'preview')->with('rows')->firstOrFail();
        DB::transaction(function () use ($batch, $workflow): void {
            foreach ($batch->rows as $row) {
                if ($row->errors) {
                    continue;
                }
                $data = $row->payload;
                $job = CareerJob::query()->create([
                    'company_id' => null,
                    'created_by_type' => 'campus_import',
                    'created_by_reference' => $batch->actor_core_user_id,
                    'employer_display_name' => trim($data['employer_display_name']),
                    'title' => trim($data['title']),
                    'employment_type' => $data['employment_type'],
                    'work_mode' => $data['work_mode'],
                    'city' => filled($data['city'] ?? null) ? $data['city'] : null,
                    'description' => $data['description'],
                    'status' => 'review',
                    'application_method' => $data['application_method'],
                    'external_apply_url' => filled($data['external_apply_url'] ?? null) ? $data['external_apply_url'] : null,
                    'external_apply_email' => filled($data['external_apply_email'] ?? null) ? $data['external_apply_email'] : null,
                    'source_type' => 'campus_import',
                    'source_name' => $data['source_name'],
                    'source_reference' => filled($data['source_reference'] ?? null) ? $data['source_reference'] : null,
                    'expires_at' => filled($data['expires_at'] ?? null) ? $data['expires_at'] : null,
                    'possible_duplicate' => $row->possible_duplicate_job_id !== null,
                    'duplicate_of_job_id' => $row->possible_duplicate_job_id,
                ]);
                $workflow->syncTags($job, array_filter(array_map('trim', explode(',', (string) ($data['tags'] ?? '')))));
                $row->update(['imported_job_id' => $job->id]);
            }
            $batch->update(['status' => 'imported', 'imported_at' => now()]);
        });

        return redirect()->route('admin.jobs.index')->with('success', 'Baris valid diimpor sebagai review. Tidak ada auto-publish.');
    }

    /** @return array<string, array<int, string>> */
    private function rules(): array
    {
        return [
            'employer_display_name' => ['required', 'string', 'max:255'],
            'title' => ['required', 'string', 'max:255'],
            'employment_type' => ['required', 'in:full_time,part_time,contract,internship,project,temporary'],
            'work_mode' => ['required', 'in:onsite,hybrid,remote'],
            'description' => ['required', 'string'],
            'application_method' => ['required', 'in:internal,external_url,email_instruction,email'],
            'source_name' => ['required', 'string', 'max:255'],
            'expires_at' => ['nullable', 'date'],
        ];
    }

    private function actor(Request $request): CareerActor
    {
        return $request->attributes->get(CareerActor::class);
    }
}
