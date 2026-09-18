<?php

namespace App\Http\Middleware;

use App\Authorization\CurrentCareerActor;
use App\Exceptions\CoreIdentityDenied;
use App\Support\CareerRoleRegistry;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function __construct(
        private readonly CurrentCareerActor $currentActor,
        private readonly CareerRoleRegistry $roles,
    ) {}

    public function share(Request $request): array
    {
        $actor = $this->currentActor->optionalFromRequest($request);

        $principal = $request->session()->get('core_principal');
        $roleContext = null;

        if (is_array($principal)) {
            try {
                $availableRoles = $this->roles->sessionRoles($principal['roles'] ?? null);
                $roleContext = [
                    'active_role' => $request->session()->get('career_active_role'),
                    'can_switch' => count($availableRoles) > 1,
                ];
            } catch (CoreIdentityDenied) {
                $roleContext = null;
            }
        }

        return [
            ...parent::share($request),
            'auth' => [
                'actor' => $actor?->toFrontendArray(),
                'role_context' => $roleContext,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
            ],
        ];
    }
}
