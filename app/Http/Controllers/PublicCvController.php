<?php

namespace App\Http\Controllers;

use App\Cv\CvShareLinks;
use App\Cv\CvSocialPreview;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class PublicCvController extends Controller
{
    public function show(Request $request, string $token, CvShareLinks $links): HttpResponse
    {
        $link = $links->resolve($token);
        $snapshot = $link->revision->snapshot;
        $name = (string) ($snapshot['professional_name'] ?? 'Alumni Farmasi UBP');
        $summary = collect($snapshot['sections'] ?? [])->firstWhere('key', 'summary')['items'][0]['description'] ?? null;
        $description = Str::limit(strip_tags((string) ($summary ?: ($snapshot['headline'] ?? null) ?: 'Profil profesional alumni Farmasi UBP di SAFA KARIR.')), 160);
        $url = $links->publicUrl($link) ?? route('public-cv.show', $token);
        $link->increment('view_count');
        $link->forceFill(['last_viewed_at' => now()])->save();

        $response = Inertia::render('Cv/Public', [
            'cv' => $snapshot,
            'share' => [
                'url' => $url,
                'allow_pdf_download' => $link->allow_pdf_download,
                'pdf_url' => $link->allow_pdf_download ? route('public-cv.pdf', $token) : null,
            ],
            'photoUrl' => $link->revision->photo_path ? route('public-cv.photo', $token) : null,
        ])->rootView('cv.public')->withViewData('social', [
            'title' => 'CV '.$name.' | SAFA KARIR',
            'name' => $name,
            'description' => $description,
            'url' => $url,
            'image' => route('public-cv.preview-image', $token),
        ])->toResponse($request);

        return $this->secure($response);
    }

    public function showNamed(Request $request, string $slug, string $token, CvShareLinks $links): HttpResponse
    {
        return $this->show($request, $token, $links);
    }

    public function previewImage(string $token, CvShareLinks $links, CvSocialPreview $preview): BinaryFileResponse
    {
        $revision = $links->resolve($token)->revision;

        return response()->file($preview->image($revision), [
            'Content-Type' => 'image/png', 'Cache-Control' => 'public, max-age=3600',
            'X-Robots-Tag' => 'noindex, nofollow', 'X-Content-Type-Options' => 'nosniff',
        ]);
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
