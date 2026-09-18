<?php

namespace App\Http\Controllers;

use App\Authorization\CareerAuthorization;
use App\Authorization\CareerCapability;
use App\Authorization\CurrentCareerActor;
use App\Contracts\CoreIdentityGateway;
use App\Exceptions\CoreIdentityDenied;
use App\Exceptions\CoreIdentityUnavailable;
use App\Services\FixtureCoreIdentityGateway;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class InternalSessionController extends Controller
{
    public function store(
        Request $request,
        CoreIdentityGateway $identity,
        CurrentCareerActor $currentActor,
        CareerAuthorization $authorization,
    ): RedirectResponse|Response {
        $credentials = $request->validate([
            'identifier' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'max:4096'],
        ]);

        $request->session()->forget('core_principal');

        try {
            $principal = $identity->authenticate($credentials['identifier'], $credentials['password']);
        } catch (CoreIdentityDenied) {
            return back()->withErrors([
                'identifier' => 'Kredensial atau akses SAFA KARIR tidak valid.',
            ])->withInput([
                'identifier' => $credentials['identifier'],
            ]);
        } catch (CoreIdentityUnavailable) {
            if ($request->expectsJson()) {
                return response(['message' => 'Layanan identitas sementara tidak tersedia.'], 503);
            }

            if (app()->environment(['local', 'testing'])) {
                $principal = (new FixtureCoreIdentityGateway(app()->environment()))->authenticate(
                    $credentials['identifier'],
                    $credentials['password'],
                );
            } else {
                return back()->withErrors([
                    'identifier' => 'Layanan identitas sementara tidak tersedia.',
                ])->withInput([
                    'identifier' => $credentials['identifier'],
                ]);
            }
        }

        $request->session()->regenerate();
        $request->session()->put('core_principal', $principal->toSessionArray());
        $actor = $currentActor->fromRequest($request);

        if ($authorization->allows($actor, CareerCapability::CandidateDashboardView)) {
            return redirect()->route('dashboard');
        }

        if ($authorization->allows($actor, CareerCapability::RegistrationQueueView)) {
            return redirect()->route('admin.registrations.index');
        }

        return redirect()->route('staff.overview');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->session()->forget('core_principal');
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
