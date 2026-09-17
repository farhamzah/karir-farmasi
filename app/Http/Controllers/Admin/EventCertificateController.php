<?php

namespace App\Http\Controllers\Admin;

use App\Data\CareerActor;
use App\Http\Controllers\Controller;
use App\Models\CareerEventCertificate;
use App\Models\CareerEventRegistration;
use App\Operational\InAppNotification;
use App\Services\CareerCertificateIssuer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EventCertificateController extends Controller
{
    public function store(Request $request, CareerEventRegistration $registration, CareerCertificateIssuer $issuer, InAppNotification $notifications): RedirectResponse
    {
        /** @var CareerActor $actor */ $actor = $request->attributes->get(CareerActor::class);
        $certificate = $issuer->issue($registration, $actor);
        $registration->loadMissing(['profile', 'event']);
        $notifications->send('candidate', $registration->profile->core_user_id, 'event.certificate.available', 'Sertifikat tersedia',
            "Sertifikat {$registration->event->title} siap dibuka.", '/events/mine', ['certificate_id' => $certificate->id]);

        return back()->with('success', 'Sertifikat diterbitkan dan disimpan privat.');
    }

    public function destroy(Request $request, CareerEventCertificate $certificate): RedirectResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:255']]);
        /** @var CareerActor $actor */ $actor = $request->attributes->get(CareerActor::class);
        $certificate->update(['revoked_at' => now(), 'revoked_by_core_user_id' => $actor->coreUserId, 'revocation_reason' => $data['reason']]);
        DB::table('audit_events')->insert(['event_type' => 'event.certificate.revoked', 'actor_reference' => $actor->coreUserId,
            'metadata' => json_encode(['certificate_id' => $certificate->id, 'registration_id' => $certificate->career_event_registration_id], JSON_THROW_ON_ERROR),
            'created_at' => now(), 'updated_at' => now()]);

        return back()->with('success', 'Sertifikat dicabut.');
    }
}
