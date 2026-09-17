<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\CoreAlumniGateway;
use App\Data\CareerActor;
use App\Exceptions\CoreAlumniOperationFailed;
use App\Http\Controllers\Controller;
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

    private function decide(Request $request, string $reference, CoreAlumniGateway $gateway, bool $approve): RedirectResponse
    {
        /** @var CareerActor $actor */
        $actor = $request->attributes->get(CareerActor::class);

        try {
            if ($approve) {
                $gateway->approve($reference, $actor->coreUserId);
            } else {
                $gateway->reject($reference, $actor->coreUserId, $request->string('reason')->toString());
            }
        } catch (CoreAlumniOperationFailed $exception) {
            return back()->withErrors(['decision' => $exception->getMessage()]);
        }

        app(InAppNotification::class)->send('registration', $reference, 'registration.decision', 'Keputusan pendaftaran',
            $approve ? 'Pendaftaran disetujui. Anda dapat masuk ke SAFA KARIR.' : 'Pendaftaran belum dapat disetujui.',
            "/status/{$reference}", ['status' => $approve ? 'approved' : 'rejected']);

        return redirect()->route('admin.registrations.show', $reference)
            ->with('success', $approve ? 'Pendaftaran disetujui.' : 'Pendaftaran ditolak.');
    }
}
