<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\CompanyUser;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CompanyDashboardController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): Response
    {
        /** @var CompanyUser $user */
        $user = $request->attributes->get(CompanyUser::class);

        return Inertia::render('Company/Dashboard', [
            'user' => ['name' => $user->name, 'role' => $user->role],
            'company' => ['name' => $user->company->display_name, 'sector' => $user->company->business_sector,
                'city' => $user->company->city, 'status' => $user->company->verification_status,
                'active' => $user->company->active, 'can_search' => $user->company->canSearchTalent()],
            'recruiterCount' => $user->company->users()->where('active', true)->count(),
            'shortlistCount' => $user->company->shortlists()->count(),
        ]);
    }
}
