<?php

namespace App\Http\Controllers;

use App\Contracts\CoreAlumniGateway;
use App\Exceptions\CoreAlumniOperationFailed;
use App\Models\CareerNotification;
use Inertia\Inertia;
use Inertia\Response;

class RegistrationStatusController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('RegistrationStatus', ['registration' => null]);
    }

    public function show(string $reference, CoreAlumniGateway $gateway): Response
    {
        try {
            $registration = $gateway->status($reference);
        } catch (CoreAlumniOperationFailed $exception) {
            $registration = null;
            $error = $exception->status === 404
                ? 'Nomor referensi tidak ditemukan.'
                : 'Status pendaftaran sementara tidak dapat diperiksa.';
        }

        return Inertia::render('RegistrationStatus', [
            'registration' => $registration,
            'lookupReference' => $reference,
            'lookupError' => $error ?? null,
            'coreRecoveryUrl' => config('core_identity.recovery_url'),
            'decisionNotice' => CareerNotification::query()
                ->where('recipient_type', 'registration')->where('recipient_reference', $reference)
                ->latest()->value('body'),
        ]);
    }
}
