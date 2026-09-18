<?php

namespace App\Http\Controllers;

use App\Data\CareerActor;
use App\Models\LeadershipAssignment;
use App\Operational\OperationalMetrics;
use App\Support\CareerRoleRegistry;
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
        $isAdministrator = in_array(CareerRoleRegistry::Administrator, $actor->roles, true);

        return Inertia::render('Staff/Overview', [
            'actor' => $actor->toFrontendArray(),
            'summary' => $metrics->forActor($actor),
            'directoryAccess' => [
                'available' => $isAdministrator || $assignments->isNotEmpty(),
                'scope' => $isAdministrator ? 'Seluruh alumni Farmasi UBP' : $assignments->pluck('scope_label')->filter()->unique()->implode(', '),
                'role' => $isAdministrator ? 'Administrator SAFA KARIR' : $assignments->pluck('role_label')->filter()->unique()->implode(', '),
            ],
        ]);
    }
}
