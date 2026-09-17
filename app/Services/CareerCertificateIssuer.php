<?php

namespace App\Services;

use App\Data\CareerActor;
use App\Models\CareerEventCertificate;
use App\Models\CareerEventRegistration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class CareerCertificateIssuer
{
    public function issue(CareerEventRegistration $registration, CareerActor $actor): CareerEventCertificate
    {
        $registration->loadMissing(['event', 'profile', 'certificate']);
        if (! $registration->completed_at || ! $registration->event->certificate_enabled) {
            throw ValidationException::withMessages(['certificate' => 'Sertifikat hanya tersedia untuk peserta yang telah menyelesaikan event.']);
        }
        if ($registration->certificate) {
            return $registration->certificate;
        }

        return DB::transaction(function () use ($registration, $actor) {
            $number = 'SAFA-EVT-'.$registration->event->starts_at->format('Ym').'-'.str_pad((string) $registration->id, 6, '0', STR_PAD_LEFT);
            $path = 'event-certificates/'.Str::uuid().'.pdf';
            Storage::disk('career_private')->put($path, $this->pdf($registration->profile->professional_name, $registration->event->title, $number));
            $certificate = $registration->certificate()->create(['certificate_number' => $number, 'verification_code' => hash('sha256', Str::random(64)),
                'issued_at' => now(), 'file_path' => $path, 'file_mime' => 'application/pdf', 'issued_by_core_user_id' => $actor->coreUserId]);
            $this->audit('event.certificate.issued', $actor->coreUserId, ['event_id' => $registration->career_event_id, 'registration_id' => $registration->id, 'certificate_id' => $certificate->id]);

            return $certificate;
        });
    }

    public function revoke(CareerEventCertificate $certificate, CareerActor $actor, string $reason): void
    {
        $certificate->update(['revoked_at' => now(), 'revoked_by_core_user_id' => $actor->coreUserId, 'revocation_reason' => $reason]);
        $this->audit('event.certificate.revoked', $actor->coreUserId, ['certificate_id' => $certificate->id, 'event_id' => $certificate->registration->career_event_id]);
    }

    private function pdf(string $name, string $event, string $number): string
    {
        $text = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], "Sertifikat SAFA KARIR | {$name} | {$event} | {$number}");
        $stream = "BT /F1 14 Tf 50 760 Td ({$text}) Tj ET";
        $objects = ["1 0 obj << /Type /Catalog /Pages 2 0 R >> endobj\n", "2 0 obj << /Type /Pages /Kids [3 0 R] /Count 1 >> endobj\n",
            "3 0 obj << /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >> endobj\n",
            "4 0 obj << /Type /Font /Subtype /Type1 /BaseFont /Helvetica >> endobj\n", '5 0 obj << /Length '.strlen($stream)." >> stream\n{$stream}\nendstream endobj\n"];
        $pdf = "%PDF-1.4\n";
        $offsets = [0];
        foreach ($objects as $object) {
            $offsets[] = strlen($pdf);
            $pdf .= $object;
        }
        $xref = strlen($pdf);
        $pdf .= "xref\n0 6\n0000000000 65535 f \n";
        foreach (array_slice($offsets, 1) as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }

        return $pdf."trailer << /Size 6 /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF";
    }

    private function audit(string $type, string $actor, array $metadata): void
    {
        DB::table('audit_events')->insert(['event_type' => $type, 'actor_reference' => $actor, 'metadata' => json_encode($metadata, JSON_THROW_ON_ERROR), 'created_at' => now(), 'updated_at' => now()]);
    }
}
