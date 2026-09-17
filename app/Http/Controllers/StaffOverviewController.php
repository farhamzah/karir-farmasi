<?php

namespace App\Http\Controllers;

use App\Data\CareerActor;
use App\Operational\OperationalMetrics;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StaffOverviewController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, OperationalMetrics $metrics): Response
    {
        /** @var CareerActor $actor */
        $actor = $request->attributes->get(CareerActor::class);

        return Inertia::render('Staff/Overview', [
            'actor' => $actor->toFrontendArray(),
            'summary' => $metrics->forActor($actor),
        ]);
    }
}
