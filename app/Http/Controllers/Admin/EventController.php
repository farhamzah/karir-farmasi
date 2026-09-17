<?php

namespace App\Http\Controllers\Admin;

use App\Authorization\CareerAuthorization;
use App\Authorization\CareerCapability;
use App\Data\CareerActor;
use App\Http\Controllers\Controller;
use App\Http\Requests\SaveCareerEventRequest;
use App\Models\CareerEvent;
use App\Models\CareerEventTopic;
use App\Talent\TalentIndexBuilder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class EventController extends Controller
{
    public function index(Request $request, CareerAuthorization $authorization): Response
    {
        /** @var CareerActor $actor */ $actor = $request->attributes->get(CareerActor::class);
        $canManage = $authorization->allows($actor, CareerCapability::EventManage);

        return Inertia::render('Admin/Events/Index', ['events' => CareerEvent::with('topics')->withCount('registrations')->latest('starts_at')->get()->map(fn ($event) => [
            'id' => $event->id, 'slug' => $event->slug, 'title' => $event->title, 'event_type' => str($event->event_type)->replace('_', ' ')->title(),
            'starts_at' => $event->starts_at->locale('id')->translatedFormat('d M Y · H.i'), 'status' => $event->status,
            'registrations_count' => $event->registrations_count, 'topics' => $event->topics->pluck('label')->all(),
            'flyer_url' => $event->flyer_path ? ($event->status === 'published' ? route('event-flyers.show', $event) : ($canManage ? route('admin.events.flyer', $event) : null)) : null,
        ])->all(), 'canManage' => $canManage]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Events/Edit', ['event' => null]);
    }

    public function store(SaveCareerEventRequest $request): RedirectResponse
    {
        return $this->persist($request, new CareerEvent);
    }

    public function edit(CareerEvent $event): Response
    {
        $event->load('topics');

        return Inertia::render('Admin/Events/Edit', ['event' => $event->toArray() + [
            'topics' => $event->topics->pluck('label')->all(),
            'has_flyer' => $event->flyer_path !== null,
            'flyer_url' => $event->flyer_path ? route('admin.events.flyer', $event) : null,
        ]]);
    }

    public function update(SaveCareerEventRequest $request, CareerEvent $event): RedirectResponse
    {
        return $this->persist($request, $event);
    }

    public function status(Request $request, CareerEvent $event): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', 'in:draft,published,closed,cancelled']]);
        $event->update($data);

        return back()->with('success', 'Status event diperbarui.');
    }

    private function persist(SaveCareerEventRequest $request, CareerEvent $event, ?TalentIndexBuilder $indexBuilder = null): RedirectResponse
    {
        $data = $request->validated();
        $topics = $data['topics'];
        $flyer = $request->file('flyer');
        $removeFlyer = (bool) ($data['remove_flyer'] ?? false);
        unset($data['topics'], $data['flyer'], $data['remove_flyer']);
        /** @var CareerActor $actor */ $actor = $request->attributes->get(CareerActor::class);
        if (! $event->exists) {
            $data += ['slug' => Str::slug($data['title']).'-'.Str::lower(Str::random(5)), 'reference' => 'EVT-'.now()->format('Ymd').'-'.Str::upper(Str::random(6)), 'created_by_core_user_id' => $actor->coreUserId, 'status' => 'draft'];
        }
        $oldFlyerPath = $event->flyer_path;
        if ($flyer !== null) {
            $data['flyer_path'] = $flyer->store('events/flyers', 'career_private');
            $data['flyer_mime'] = $flyer->getMimeType();
            $data['flyer_alt_text'] = $data['flyer_alt_text'] ?: 'Flyer '.$data['title'];
        } elseif ($removeFlyer) {
            $data['flyer_path'] = null;
            $data['flyer_mime'] = null;
            $data['flyer_alt_text'] = null;
        }
        $event->fill($data)->save();
        if (($flyer !== null || $removeFlyer) && $oldFlyerPath !== null) {
            Storage::disk('career_private')->delete($oldFlyerPath);
        }
        $topicIds = collect($topics)->map(function ($label) {
            $slug = Str::slug($label);

            return CareerEventTopic::firstOrCreate(['slug' => $slug], ['label' => $label])->id;
        });
        $event->topics()->sync($topicIds);
        $indexBuilder ??= app(TalentIndexBuilder::class);
        $event->registrations()->with('profile')->whereNotNull('completed_at')->get()
            ->each(fn ($registration) => $indexBuilder->rebuild($registration->profile));

        return redirect()->route('admin.events.index')->with('success', $event->wasRecentlyCreated ? 'Event dibuat sebagai draft.' : 'Event diperbarui.');
    }
}
