<?php

namespace App\Http\Controllers;

use App\Data\CareerActor;
use App\Models\CareerEvent;
use App\Models\CareerEventRegistration;
use App\Models\CareerProfile;
use App\Services\CareerEventService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EventRegistrationController extends Controller
{
    public function store(Request $request, CareerEvent $event, CareerEventService $service): RedirectResponse
    {
        /** @var CareerActor $actor */ $actor = $request->attributes->get(CareerActor::class);
        $profile = CareerProfile::where('core_user_id', $actor->coreUserId)->firstOrFail();
        $service->register($event, $profile);

        return back()->with('success', 'Pendaftaran event berhasil. Pantau statusnya di Event Saya.');
    }

    public function destroy(Request $request, CareerEvent $event, CareerEventRegistration $registration, CareerEventService $service): RedirectResponse
    {
        /** @var CareerActor $actor */ $actor = $request->attributes->get(CareerActor::class);
        abort_unless($registration->career_event_id === $event->id && $registration->ownerCoreUserId() === $actor->coreUserId, 404);
        $service->cancel($registration);

        return back()->with('success', 'Pendaftaran event dibatalkan.');
    }
}
