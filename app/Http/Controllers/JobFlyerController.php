<?php

namespace App\Http\Controllers;

use App\Models\CareerJob;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class JobFlyerController extends Controller
{
    public function show(string $reference): StreamedResponse
    {
        $job = CareerJob::where('public_reference', $reference)->firstOrFail();
        abort_unless($job->isPublishedAndOpen() && $job->flyer_path !== null, 404);

        return $this->response($job, 'public, max-age=300');
    }

    public function admin(string $reference): StreamedResponse
    {
        $job = CareerJob::where('public_reference', $reference)->firstOrFail();
        abort_if($job->flyer_path === null, 404);

        return $this->response($job, 'private, no-store');
    }

    private function response(CareerJob $job, string $cacheControl): StreamedResponse
    {
        abort_unless(Storage::disk('career_private')->exists($job->flyer_path), 404);

        return Storage::disk('career_private')->response($job->flyer_path, null, [
            'Cache-Control' => $cacheControl,
            'Content-Type' => Storage::disk('career_private')->mimeType($job->flyer_path) ?: 'image/jpeg',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
