<?php

namespace App\Http\Controllers;

use App\Cv\CareerCvProjection;
use App\Cv\CvPublisher;
use App\Cv\CvShareLinks;
use App\Data\CareerActor;
use App\Models\CareerProfile;
use App\Policies\CareerCvPolicy;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CareerCvPreviewController extends Controller
{
    public function show(Request $request, int $cv, CareerCvProjection $projection, CareerCvPolicy $policy, CvPublisher $publisher, CvShareLinks $links): Response
    {
        $actor = $request->attributes->get(CareerActor::class);
        $profile = CareerProfile::where('core_user_id', $actor->coreUserId)->firstOrFail();
        $careerCv = $profile->cvs()->findOrFail($cv);
        abort_unless($policy->view($actor, $careerCv), 404);

        $careerCv->load(['latestPublishedRevision', 'shareLinks.revision']);
        $latest = $careerCv->latestPublishedRevision;

        return Inertia::render('Cv/Preview', [
            'cv' => $projection->preview($careerCv),
            'publishing' => [
                'published' => $latest !== null,
                'revision_number' => $latest?->revision_number,
                'published_at' => $latest?->published_at?->toAtomString(),
                'has_unpublished_changes' => $latest !== null && $latest->content_checksum !== $publisher->draftChecksum($careerCv),
            ],
            'shareLinks' => $careerCv->shareLinks
                ->map(function ($link) use ($links) {
                    $token = $link->safeToken();

                    return [
                        'id' => $link->public_id,
                        'label' => $link->label,
                        'active' => $link->active,
                        'expires_at' => $link->expires_at?->toAtomString(),
                        'allow_pdf_download' => $link->allow_pdf_download,
                        'follow_latest_published' => $link->follow_latest_published,
                        'revision_number' => $link->revision?->revision_number,
                        'view_count' => $link->view_count,
                        'last_viewed_at' => $link->last_viewed_at?->toAtomString(),
                        'url' => $links->publicUrl($link),
                        'token_available' => $token !== null,
                    ];
                })
                ->all(),
        ]);
    }
}
