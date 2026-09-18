<?php

namespace App\Http\Controllers;

use App\Data\CareerActor;
use App\Models\LeadershipAssignment;
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
        $assignments = LeadershipAssignment::query()
            ->where('actor_core_user_id', $actor->coreUserId)
            ->where('active', true)
            ->get()
            ->filter->isCurrent();

        return Inertia::render('Staff/Overview', [
            'actor' => $actor->toFrontendArray(),
            'summary' => $metrics->forActor($actor),
            'directoryAccess' => [
                'available' => $assignments->isNotEmpty(),
                'scope' => $assignments->pluck('scope_label')->filter()->unique()->implode(', '),
                'role' => $assignments->pluck('role_label')->filter()->unique()->implode(', '),
            ],
        ]);
    }
}
