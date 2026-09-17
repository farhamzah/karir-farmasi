<?php

namespace App\Http\Controllers;

use App\Data\CareerActor;
use App\Models\CareerEvent;
use App\Models\CareerProfile;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EventController extends Controller
{
    public function index(Request $request): Response
    {
        $profile = $this->profile($request);
        $events = CareerEvent::query()->where('status', 'published')->with('topics')->withCount(['registrations as registrations_count' => fn ($query) => $query->whereNull('cancelled_at')])
            ->orderBy('starts_at')->get()->map(fn ($event) => $this->data($event, $profile))->all();

        return Inertia::render('Event/Index', ['events' => $events]);
    }

    public function show(Request $request, CareerEvent $event): Response
    {
        abort_unless($event->status === 'published', 404);
        $event->load('topics')->loadCount(['registrations as registrations_count' => fn ($query) => $query->whereNull('cancelled_at')]);

        return Inertia::render('Event/Show', ['event' => $this->data($event, $this->profile($request))]);
    }

    public function mine(Request $request): Response
    {
        $profile = $this->profile($request);
        $registrations = $profile?->eventRegistrations()->with(['event.topics', 'certificate'])->latest('registered_at')->get()->map(fn ($registration) => [
            'id' => $registration->id, 'status' => $registration->status, 'role' => $this->role($registration->role),
            'attended' => $registration->attended_at !== null, 'completed' => $registration->completed_at !== null,
            'event' => $this->data($registration->event, $profile),
            'certificate' => $registration->certificate && ! $registration->certificate->revoked_at ? [
                'number' => $registration->certificate->certificate_number, 'issued_at' => $registration->certificate->issued_at->locale('id')->translatedFormat('d M Y'),
                'download_url' => route('events.certificate', $registration->certificate),
            ] : null,
        ])->all() ?? [];

        return Inertia::render('Event/MyEvents', ['registrations' => $registrations]);
    }

    private function profile(Request $request): ?CareerProfile
    {
        /** @var CareerActor $actor */ $actor = $request->attributes->get(CareerActor::class);

        return CareerProfile::where('core_user_id', $actor->coreUserId)->first();
    }

    private function data(CareerEvent $event, ?CareerProfile $profile): array
    {
        $registration = $profile ? $event->registrations()->where('career_profile_id', $profile->id)->first() : null;

        return ['id' => $event->id, 'slug' => $event->slug, 'title' => $event->title, 'event_type' => str($event->event_type)->replace('_', ' ')->title(),
            'organizer' => $event->organizer, 'description' => $event->description, 'starts_at' => $event->starts_at->locale('id')->translatedFormat('d M Y · H.i'),
            'ends_at' => $event->ends_at->locale('id')->translatedFormat('d M Y · H.i'), 'location_type' => $event->location_type,
            'location_text' => $event->location_text, 'topics' => $event->topics->pluck('label')->all(), 'capacity' => $event->capacity,
            'registrations_count' => $event->registrations_count ?? 0, 'registered' => $registration && ! $registration->cancelled_at,
            'registration_id' => $registration?->id, 'registration_open' => (! $event->registration_opens_at || now()->gte($event->registration_opens_at))
                && (! $event->registration_closes_at || now()->lte($event->registration_closes_at))];
    }

    private function role(string $role): string
    {
        return ['participant' => 'Peserta', 'committee' => 'Panitia', 'speaker' => 'Pembicara', 'moderator' => 'Moderator', 'mentor' => 'Mentor / Fasilitator'][$role] ?? str($role)->title();
    }
}
