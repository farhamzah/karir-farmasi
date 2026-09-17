<?php

namespace App\Http\Controllers\Admin;

use App\Data\CareerActor;
use App\Http\Controllers\Controller;
use App\Models\CareerEvent;
use App\Models\CareerEventRegistration;
use App\Services\CareerEventService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EventParticipantController extends Controller
{
    public function index(CareerEvent $event): Response
    {
        $event->load(['registrations.profile', 'registrations.certificate']);

        return Inertia::render('Admin/Events/Participants', ['event' => ['id' => $event->id, 'title' => $event->title],
            'registrations' => $event->registrations->map(fn ($registration) => ['id' => $registration->id,
                'name' => $registration->profile->professional_name, 'role' => $registration->role, 'status' => $registration->status,
                'attended' => $registration->attended_at !== null, 'completed' => $registration->completed_at !== null,
                'certificate' => $registration->certificate?->certificate_number, 'certificate_revoked' => (bool) $registration->certificate?->revoked_at,
            ])->all()]);
    }

    public function update(Request $request, CareerEvent $event, CareerEventRegistration $registration, CareerEventService $service): RedirectResponse
    {
        abort_unless($registration->career_event_id === $event->id, 404);
        $data = $request->validate(['role' => ['required', 'in:participant,committee,speaker,moderator,mentor'], 'attended' => ['required', 'boolean'], 'completed' => ['required', 'boolean']]);
        /** @var CareerActor $actor */ $actor = $request->attributes->get(CareerActor::class);
        $registration->update(['role' => $data['role']]);
        $service->attendance($registration, $data['attended'], $actor);
        $service->completion($registration->fresh(), $data['completed'], $actor);

        return back()->with('success', 'Kehadiran dan penyelesaian diperbarui.');
    }
}
