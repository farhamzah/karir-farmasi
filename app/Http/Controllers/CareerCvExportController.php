<?php

namespace App\Http\Controllers;

use App\Contracts\CvPdfRenderer;
use App\Cv\CareerCvProjection;
use App\Cv\CvDocxExporter;
use App\Cv\CvShareLinks;
use App\Data\CareerActor;
use App\Models\CareerCv;
use App\Models\CareerProfile;
use App\Models\CvPublishedRevision;
use App\Policies\CareerCvPolicy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class CareerCvExportController extends Controller
{
    public function pdf(Request $request, int $cv, CareerCvProjection $projection, CvPdfRenderer $renderer, CareerCvPolicy $policy): BinaryFileResponse
    {
        $careerCv = $this->ownedCv($request, $cv);
        abort_unless($policy->view($this->actor($request), $careerCv), 404);
        $path = $renderer->render($projection->preview($careerCv), $this->profilePhotoData($careerCv));

        return response()->download($path, $this->filename($careerCv, 'pdf'), ['Content-Type' => 'application/pdf'])->deleteFileAfterSend(true);
    }

    public function docx(Request $request, int $cv, CareerCvProjection $projection, CvDocxExporter $exporter, CareerCvPolicy $policy): BinaryFileResponse
    {
        $careerCv = $this->ownedCv($request, $cv);
        abort_unless($policy->view($this->actor($request), $careerCv), 404);
        $path = $exporter->export($projection->preview($careerCv), $this->profilePhotoData($careerCv));

        return response()->download($path, $this->filename($careerCv, 'docx'), [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ])->deleteFileAfterSend(true);
    }

    public function publicPdf(string $token, CvShareLinks $links, CvPdfRenderer $renderer): BinaryFileResponse
    {
        $link = $links->resolve($token);
        abort_unless($link->allow_pdf_download, 404);
        $revision = $link->revision;
        $path = $renderer->render($revision->snapshot, $this->revisionPhotoData($revision));

        return response()->download($path, 'cv-'.Str::slug((string) $revision->snapshot['professional_name']).'.pdf', [
            'Content-Type' => 'application/pdf', 'X-Robots-Tag' => 'noindex, nofollow',
            'Referrer-Policy' => 'no-referrer', 'X-Content-Type-Options' => 'nosniff',
        ])->deleteFileAfterSend(true);
    }

    private function ownedCv(Request $request, int $id): CareerCv
    {
        return CareerProfile::where('core_user_id', $this->actor($request)->coreUserId)->firstOrFail()->cvs()->findOrFail($id);
    }

    private function actor(Request $request): CareerActor
    {
        return $request->attributes->get(CareerActor::class);
    }

    private function filename(CareerCv $cv, string $extension): string
    {
        return Str::limit(Str::slug($cv->name), 80, '').'.'.$extension;
    }

    private function profilePhotoData(CareerCv $cv): ?string
    {
        return $this->photoData($cv->profile->photo_path);
    }

    private function revisionPhotoData(CvPublishedRevision $revision): ?string
    {
        return $this->photoData($revision->photo_path);
    }

    private function photoData(?string $path): ?string
    {
        if (! $path || ! Storage::disk('career_private')->exists($path)) {
            return null;
        }
        $mime = Storage::disk('career_private')->mimeType($path) ?: 'image/jpeg';

        return 'data:'.$mime.';base64,'.base64_encode(Storage::disk('career_private')->get($path));
    }
}
