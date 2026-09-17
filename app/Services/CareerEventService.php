<?php

namespace App\Services;

use App\Data\CareerActor;
use App\Models\CareerEvent;
use App\Models\CareerEventRegistration;
use App\Models\CareerProfile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class CareerEventService
{
    public function register(CareerEvent $event, CareerProfile $profile): CareerEventRegistration
    {
        if ($event->status !== 'published' || ($event->registration_opens_at && now()->isBefore($event->registration_opens_at))
            || ($event->registration_closes_at && now()->isAfter($event->registration_closes_at)) || now()->gte($event->starts_at)) {
            throw ValidationException::withMessages(['event' => 'Pendaftaran event belum tersedia atau sudah ditutup.']);
        }

        return DB::transaction(function () use ($event, $profile) {
            $locked = CareerEvent::query()->lockForUpdate()->findOrFail($event->id);
            $existing = $locked->registrations()->where('career_profile_id', $profile->id)->first();
            if ($existing && $existing->cancelled_at === null) {
                return $existing;
            }
            $activeCount = $locked->registrations()->whereNull('cancelled_at')->count();
            if ($locked->capacity !== null && $activeCount >= $locked->capacity) {
                throw ValidationException::withMessages(['event' => 'Kuota event sudah penuh.']);
            }

            $registration = $existing ?: new CareerEventRegistration;
            $registration->fill(['career_event_id' => $locked->id, 'career_profile_id' => $profile->id, 'role' => 'participant',
                'status' => 'registered', 'registered_at' => now(), 'attended_at' => null, 'completed_at' => null, 'cancelled_at' => null])->save();
            $this->audit('event.registration.created', $profile->core_user_id, ['event_id' => $locked->id, 'registration_id' => $registration->id]);

            return $registration;
        });
    }

    public function cancel(CareerEventRegistration $registration): void
    {
        if ($registration->completed_at) {
            throw ValidationException::withMessages(['event' => 'Event yang sudah selesai tidak dapat dibatalkan.']);
        }
        $registration->update(['status' => 'cancelled', 'cancelled_at' => now()]);
        $this->audit('event.registration.cancelled', $registration->ownerCoreUserId(), ['event_id' => $registration->career_event_id, 'registration_id' => $registration->id]);
    }

    public function attendance(CareerEventRegistration $registration, bool $attended, CareerActor $actor): void
    {
        $registration->update(['attended_at' => $attended ? now() : null, 'status' => $attended ? 'attended' : 'registered']);
        $this->audit('event.attendance.updated', $actor->coreUserId, ['event_id' => $registration->career_event_id, 'registration_id' => $registration->id, 'attended' => $attended]);
    }

    public function completion(CareerEventRegistration $registration, bool $completed, CareerActor $actor): void
    {
        if ($completed && ! $registration->attended_at) {
            throw ValidationException::withMessages(['completion' => 'Kehadiran harus dicatat sebelum penyelesaian.']);
        }
        $registration->update(['completed_at' => $completed ? now() : null, 'status' => $completed ? 'completed' : 'attended']);
        $this->audit('event.completion.updated', $actor->coreUserId, ['event_id' => $registration->career_event_id, 'registration_id' => $registration->id, 'completed' => $completed]);
    }

    private function audit(string $type, string $actor, array $metadata): void
    {
        DB::table('audit_events')->insert(['event_type' => $type, 'actor_reference' => $actor, 'metadata' => json_encode($metadata, JSON_THROW_ON_ERROR), 'created_at' => now(), 'updated_at' => now()]);
    }
}
