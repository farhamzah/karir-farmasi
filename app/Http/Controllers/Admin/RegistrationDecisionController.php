<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\CoreAlumniGateway;
use App\Data\CareerActor;
use App\Exceptions\CoreAlumniOperationFailed;
use App\Http\Controllers\Controller;
use App\Models\CareerProfile;
use App\Operational\InAppNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RegistrationDecisionController extends Controller
{
    public function approve(Request $request, string $reference, CoreAlumniGateway $gateway): RedirectResponse
    {
        return $this->decide($request, $reference, $gateway, true);
    }

    public function reject(Request $request, string $reference, CoreAlumniGateway $gateway): RedirectResponse
    {
        $request->validate(['reason' => ['required', 'string', 'min:3', 'max:500']]);

        return $this->decide($request, $reference, $gateway, false);
    }

    public function bulkApprove(Request $request, CoreAlumniGateway $gateway): RedirectResponse
    {
        $data = $request->validate([
            'references' => ['required', 'array', 'min:1', 'max:100'],
            'references.*' => ['required', 'string', 'max:100', 'distinct'],
        ]);

        /** @var CareerActor $actor */
        $actor = $request->attributes->get(CareerActor::class);
        $approved = [];
        $failed = [];

        foreach ($data['references'] as $reference) {
            try {
                $registration = $gateway->registration($reference);
                if (! in_array($registration['status'] ?? null, ['pending', 'manual_review'], true)) {
                    $failed[] = $reference;

                    continue;
                }

                $result = $gateway->approve($reference, $actor->coreUserId);
                $this->syncAlumniDirectoryProfile($registration, $result);
                $this->sendDecisionNotification($reference, true);
                $approved[] = $reference;
            } catch (CoreAlumniOperationFailed) {
                $failed[] = $reference;
            }
        }

        $response = back()->with('success', count($approved).' pendaftaran berhasil disetujui.');
        if ($failed !== []) {
            $response->withErrors([
                'bulk' => count($failed).' pendaftaran belum berhasil diproses. Periksa kembali data tersebut satu per satu.',
            ]);
        }

        return $response;
    }

    private function decide(Request $request, string $reference, CoreAlumniGateway $gateway, bool $approve): RedirectResponse
    {
        /** @var CareerActor $actor */
        $actor = $request->attributes->get(CareerActor::class);

        try {
            if ($approve) {
                $registration = $gateway->registration($reference);
                $result = $gateway->approve($reference, $actor->coreUserId);
                $this->syncAlumniDirectoryProfile($registration, $result);
            } else {
                $gateway->reject($reference, $actor->coreUserId, $request->string('reason')->toString());
            }
        } catch (CoreAlumniOperationFailed $exception) {
            return back()->withErrors(['decision' => $exception->getMessage()]);
        }

        $this->sendDecisionNotification($reference, $approve);

        return redirect()->route('admin.registrations.show', $reference)
            ->with('success', $approve ? 'Pendaftaran disetujui.' : 'Pendaftaran ditolak.');
    }

    private function sendDecisionNotification(string $reference, bool $approved): void
    {
        app(InAppNotification::class)->send('registration', $reference, 'registration.decision', 'Keputusan pendaftaran',
            $approved ? 'Pendaftaran disetujui. Anda dapat masuk ke SAFA KARIR.' : 'Pendaftaran belum dapat disetujui.',
            "/status/{$reference}", ['status' => $approved ? 'approved' : 'rejected']);
    }

    /**
     * @param  array<string, mixed>  $registration
     * @param  array<string, mixed>  $approval
     */
    private function syncAlumniDirectoryProfile(array $registration, array $approval): void
    {
        $coreUserId = $approval['core_user_id'] ?? null;
        $alumniNumber = $registration['student_number'] ?? null;

        if (! is_string($coreUserId) || $coreUserId === '' || ! is_string($alumniNumber) || $alumniNumber === '') {
            return;
        }

        $profile = CareerProfile::firstOrNew(['core_user_id' => $coreUserId]);
        $profile->alumni_number = $alumniNumber;
        $profile->graduation_year = is_numeric($registration['graduation_year'] ?? null)
            ? (int) $registration['graduation_year']
            : null;

        if (! $profile->exists) {
            $profile->visible_in_alumni_directory = true;
        }

        if (! filled($profile->professional_name) && is_string($registration['full_name'] ?? null)) {
            $profile->professional_name = $registration['full_name'];
        }

        $profile->save();
    }
}
