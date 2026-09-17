<?php

namespace App\Http\Controllers;

use App\Data\CareerActor;
use App\Models\CareerEventCertificate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EventCertificateController extends Controller
{
    public function show(Request $request, CareerEventCertificate $certificate): StreamedResponse
    {
        /** @var CareerActor $actor */ $actor = $request->attributes->get(CareerActor::class);
        abort_unless(! $certificate->revoked_at && $certificate->ownerCoreUserId() === $actor->coreUserId, 404);
        abort_unless(Storage::disk('career_private')->exists($certificate->file_path), 404);

        return Storage::disk('career_private')->download($certificate->file_path, 'Sertifikat-'.$certificate->certificate_number.'.pdf', [
            'Content-Type' => $certificate->file_mime, 'Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
