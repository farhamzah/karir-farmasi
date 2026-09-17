<?php

namespace App\Http\Controllers;

use App\Data\CareerActor;
use App\Models\CareerApplicationDocument;
use App\Models\CareerJobApplication;
use App\Models\CompanyUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ApplicationDocumentController extends Controller
{
    public function store(Request $request, string $reference): RedirectResponse
    {
        $data = $request->validate([
            'type' => ['required', 'in:cover_letter,supporting_document'],
            'document' => ['required', 'file', 'mimes:pdf,doc,docx', 'max:5120'],
        ]);
        $application = $this->candidateApplication($request, $reference);
        $file = $request->file('document');
        $existing = $application->documents()->where('type', $data['type'])->first();
        if ($existing) {
            Storage::disk($existing->disk)->delete($existing->path);
            $existing->delete();
        }
        $path = $file->store("applications/{$application->public_reference}", 'career_private');
        $application->documents()->create([
            'type' => $data['type'],
            'disk' => 'career_private',
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime' => $file->getMimeType() ?: 'application/octet-stream',
            'size' => $file->getSize(),
            'uploaded_at' => now(),
        ]);

        return back()->with('success', 'Dokumen tersimpan privat.');
    }

    public function candidateDownload(Request $request, string $reference, CareerApplicationDocument $document): StreamedResponse
    {
        $application = $this->candidateApplication($request, $reference);
        abort_unless($document->career_job_application_id === $application->id, 404);

        return Storage::disk($document->disk)->download($document->path, $document->original_name);
    }

    public function companyDownload(Request $request, string $reference, CareerApplicationDocument $document): StreamedResponse
    {
        /** @var CompanyUser $user */
        $user = $request->attributes->get(CompanyUser::class);
        $application = CareerJobApplication::query()->where('public_reference', $reference)
            ->whereHas('job', fn ($query) => $query->where('company_id', $user->company_id))->firstOrFail();
        abort_unless($document->career_job_application_id === $application->id, 404);

        return Storage::disk($document->disk)->download($document->path, $document->original_name);
    }

    public function adminDownload(string $reference, CareerApplicationDocument $document): StreamedResponse
    {
        $application = CareerJobApplication::query()->where('public_reference', $reference)->firstOrFail();
        abort_unless($document->career_job_application_id === $application->id, 404);

        return Storage::disk($document->disk)->download($document->path, $document->original_name);
    }

    private function candidateApplication(Request $request, string $reference): CareerJobApplication
    {
        /** @var CareerActor $actor */
        $actor = $request->attributes->get(CareerActor::class);

        return CareerJobApplication::query()->where('public_reference', $reference)
            ->whereHas('profile', fn ($query) => $query->where('core_user_id', $actor->coreUserId))->firstOrFail();
    }
}
