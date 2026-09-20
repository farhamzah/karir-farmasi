<?php

namespace App\Http\Controllers;

use App\Authorization\CareerCapability;
use App\Authorization\CurrentCareerActor;
use App\Data\CareerActor;
use App\Models\CareerEvent;
use App\Models\CareerProfile;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EventController extends Controller
{
    public function index(Request $request, CurrentCareerActor $currentActor): Response
    {
        $actor = $currentActor->optionalFromRequest($request);
        $profile = $this->profile($actor);
        $events = CareerEvent::query()->where('status', 'published')->where('ends_at', '>=', now())->with('topics')->withCount(['registrations as registrations_count' => fn ($query) => $query->whereNull('cancelled_at')])
            ->orderBy('starts_at')->get()->map(fn ($event) => $this->data($event, $profile, $actor))->all();

        return Inertia::render('Event/Index', ['events' => $events, 'canRegister' => $this->canRegister($actor)]);
    }

    public function show(Request $request, CareerEvent $event, CurrentCareerActor $currentActor): Response
    {
        abort_unless($event->status === 'published', 404);
        $event->load('topics')->loadCount(['registrations as registrations_count' => fn ($query) => $query->whereNull('cancelled_at')]);

        $actor = $currentActor->optionalFromRequest($request);

        return Inertia::render('Event/Show', [
            'event' => $this->data($event, $this->profile($actor), $actor),
            'canRegister' => $this->canRegister($actor),
        ]);
    }

    public function mine(Request $request): Response
    {
        /** @var CareerActor $actor */ $actor = $request->attributes->get(CareerActor::class);
        $profile = $this->profile($actor);
        $registrations = $profile?->eventRegistrations()->with(['event.topics', 'certificate'])->latest('registered_at')->get()->map(fn ($registration) => [
            'id' => $registration->id, 'status' => $registration->status, 'role' => $this->role($registration->role),
            'attended' => $registration->attended_at !== null, 'completed' => $registration->completed_at !== null,
            'event' => $this->data($registration->event, $profile, $actor),
            'certificate' => $registration->certificate && ! $registration->certificate->revoked_at ? [
                'number' => $registration->certificate->certificate_number, 'issued_at' => $registration->certificate->issued_at->locale('id')->translatedFormat('d M Y'),
                'download_url' => route('events.certificate', $registration->certificate),
            ] : null,
        ])->all() ?? [];

        return Inertia::render('Event/MyEvents', ['registrations' => $registrations]);
    }

    private function profile(?CareerActor $actor): ?CareerProfile
    {
        return $actor ? CareerProfile::where('core_user_id', $actor->coreUserId)->first() : null;
    }

    private function data(CareerEvent $event, ?CareerProfile $profile, ?CareerActor $actor): array
    {
        $registration = $profile ? $event->registrations()->where('career_profile_id', $profile->id)->first() : null;

        $activeRegistrationCount = $event->registrations_count ?? 0;
        $registrationStarted = ! $event->registration_opens_at || now()->gte($event->registration_opens_at);
        $registrationEnded = ($event->registration_closes_at && now()->gt($event->registration_closes_at)) || now()->gte($event->starts_at);
        $registrationFull = $event->capacity !== null && $activeRegistrationCount >= $event->capacity;

        return ['id' => $event->id, 'slug' => $event->slug, 'title' => $event->title, 'event_type' => str($event->event_type)->replace('_', ' ')->title(),
            'organizer' => $event->organizer, 'description' => $event->description, 'starts_at' => $event->starts_at->locale('id')->translatedFormat('d M Y · H.i'),
            'ends_at' => $event->ends_at->locale('id')->translatedFormat('d M Y · H.i'), 'location_type' => $event->location_type,
            'location_text' => $event->location_text, 'topics' => $event->topics->pluck('label')->all(), 'capacity' => $event->capacity,
            'registrations_count' => $activeRegistrationCount, 'remaining_capacity' => $event->capacity === null ? null : max(0, $event->capacity - $activeRegistrationCount),
            'registered' => $registration && ! $registration->cancelled_at, 'registration_id' => $registration?->id,
            'registration_open' => $registrationStarted && ! $registrationEnded && ! $registrationFull,
            'registration_state' => ! $registrationStarted ? 'upcoming' : ($registrationEnded ? 'closed' : ($registrationFull ? 'full' : 'open')),
            'registration_opens_at' => $event->registration_opens_at?->locale('id')->translatedFormat('d M Y · H.i'),
            'registration_closes_at' => $event->registration_closes_at?->locale('id')->translatedFormat('d M Y · H.i'),
            'registration_notes' => $event->registration_notes,
            'flyer_url' => $event->flyer_path ? route('event-flyers.show', $event) : null,
            'flyer_alt_text' => $event->flyer_alt_text ?: 'Flyer '.$event->title,
            'published_at' => $event->created_at->locale('id')->translatedFormat('d M Y'),
            'can_register' => $this->canRegister($actor),
        ];
    }

    private function canRegister(?CareerActor $actor): bool
    {
        return $actor && in_array(CareerCapability::EventRegisterOwn, $actor->capabilities, true);
    }

    private function role(string $role): string
    {
        return ['participant' => 'Peserta', 'committee' => 'Panitia', 'speaker' => 'Pembicara', 'moderator' => 'Moderator', 'mentor' => 'Mentor / Fasilitator'][$role] ?? str($role)->title();
    }
}
