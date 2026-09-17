<?php

namespace App\Http\Controllers;

use App\Data\CareerActor;
use App\Models\CareerNotification;
use App\Models\TracerPeriod;
use App\Profile\CareerProfileStore;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CandidateDashboardController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, CareerProfileStore $profiles): Response
    {
        /** @var CareerActor $actor */
        $actor = $request->attributes->get(CareerActor::class);
        $profile = $profiles->find($actor);

        return Inertia::render('Dashboard', [
            'principal' => $actor->toFrontendArray(includeEmail: true),
            'operations' => [
                'notifications_unread' => CareerNotification::query()->where('recipient_type', 'candidate')->where('recipient_reference', $actor->coreUserId)->whereNull('read_at')->count(),
                'tracer_open' => TracerPeriod::query()->where('status', 'published')->whereDate('starts_on', '<=', today())->whereDate('ends_on', '>=', today())->count(),
                'tracer_submitted' => $profile?->tracerSubmissions()->where('status', 'submitted')->count() ?? 0,
            ],
        ]);
    }
}
