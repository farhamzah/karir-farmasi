<?php

namespace App\Http\Controllers;

use App\Authorization\CareerAuthorization;
use App\Authorization\CareerCapability;
use App\Authorization\CurrentCareerActor;
use App\Support\CareerRoleRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CareerRoleSelectionController extends Controller
{
    public function show(Request $request, CareerRoleRegistry $roles): Response|RedirectResponse
    {
        $principal = $request->session()->get('core_principal');
        $availableRoles = $roles->sessionRoles($principal['roles'] ?? null);

        if (count($availableRoles) === 1) {
            $request->session()->put('career_active_role', $availableRoles[0]);

            return $this->redirectForActiveRole($request);
        }

        return Inertia::render('RoleSelection', [
            'displayName' => $principal['display_name'],
            'roles' => $roles->options($availableRoles),
            'activeRole' => $request->session()->get('career_active_role'),
        ]);
    }

    public function store(Request $request, CareerRoleRegistry $roles): RedirectResponse
    {
        $principal = $request->session()->get('core_principal');
        $availableRoles = $roles->sessionRoles($principal['roles'] ?? null);
        $validated = $request->validate([
            'role' => ['required', 'string', Rule::in($availableRoles)],
        ]);

        $request->session()->put('career_active_role', $validated['role']);

        return $this->redirectForActiveRole($request);
    }

    private function redirectForActiveRole(Request $request): RedirectResponse
    {
        $actor = app(CurrentCareerActor::class)->fromRequest($request);
        $authorization = app(CareerAuthorization::class);

        if ($authorization->allows($actor, CareerCapability::CandidateDashboardView)) {
            return redirect()->route('dashboard');
        }

        if ($authorization->allows($actor, CareerCapability::RegistrationQueueView)) {
            return redirect()->route('admin.registrations.index');
        }

        return redirect()->route('staff.overview');
    }
}
