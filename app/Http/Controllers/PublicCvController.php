<?php

namespace App\Http\Controllers;

use App\Cv\CvShareLinks;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class PublicCvController extends Controller
{
    public function show(Request $request, string $token, CvShareLinks $links): HttpResponse
    {
        $link = $links->resolve($token);
        $link->increment('view_count');
        $link->forceFill(['last_viewed_at' => now()])->save();

        $response = Inertia::render('Cv/Public', [
            'cv' => $link->revision->snapshot,
            'share' => [
                'url' => route('public-cv.show', $token),
                'allow_pdf_download' => $link->allow_pdf_download,
                'pdf_url' => $link->allow_pdf_download ? route('public-cv.pdf', $token) : null,
            ],
            'photoUrl' => $link->revision->photo_path ? route('public-cv.photo', $token) : null,
        ])->toResponse($request);

        return $this->secure($response);
    }

    public function photo(string $token, CvShareLinks $links): StreamedResponse
    {
        $revision = $links->resolve($token)->revision;
        abort_if($revision->photo_path === null || ! Storage::disk('career_private')->exists($revision->photo_path), 404);

        return Storage::disk('career_private')->response($revision->photo_path, null, [
            'Cache-Control' => 'private, max-age=300', 'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function secure(HttpResponse $response): HttpResponse
    {
        $response->headers->add([
            'X-Robots-Tag' => 'noindex, nofollow',
            'Referrer-Policy' => 'no-referrer',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'self'; img-src 'self' data:; style-src 'self' 'unsafe-inline'; script-src 'self'; connect-src 'self' ws: wss:",
        ]);

        return $response;
    }
}
