<?php

namespace App\Http\Middleware;

use App\Authorization\CurrentCareerActor;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function __construct(private readonly CurrentCareerActor $currentActor) {}

    public function share(Request $request): array
    {
        $actor = $this->currentActor->optionalFromRequest($request);

        return [
            ...parent::share($request),
            'auth' => ['actor' => $actor?->toFrontendArray()],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
            ],
        ];
    }
}
