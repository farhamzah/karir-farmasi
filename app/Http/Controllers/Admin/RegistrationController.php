<?php

namespace App\Http\Controllers\Admin;

use App\Authorization\CareerAuthorization;
use App\Authorization\CareerCapability;
use App\Contracts\CoreAlumniGateway;
use App\Data\CareerActor;
use App\Exceptions\CoreAlumniOperationFailed;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RegistrationController extends Controller
{
    public function index(
        Request $request,
        CoreAlumniGateway $gateway,
        CareerAuthorization $authorization,
    ): Response {
        $status = $request->string('status')->toString() ?: 'pending';

        try {
            $result = $gateway->registrations($status, max(1, $request->integer('page', 1)));
        } catch (CoreAlumniOperationFailed $exception) {
            $result = ['data' => [], 'meta' => []];
            $error = $exception->getMessage();
        }

        /** @var CareerActor $actor */
        $actor = $request->attributes->get(CareerActor::class);

        return Inertia::render('Admin/Registrations', [
            'registrations' => $result['data'],
            'pagination' => $result['meta'],
            'activeStatus' => $status,
            'loadError' => $error ?? null,
            'canApprove' => $authorization->allows($actor, CareerCapability::RegistrationApprove),
        ]);
    }

    public function show(
        Request $request,
        string $reference,
        CoreAlumniGateway $gateway,
        CareerAuthorization $authorization,
    ): Response {
        try {
            $registration = $gateway->registration($reference);
        } catch (CoreAlumniOperationFailed $exception) {
            abort($exception->status === 404 ? 404 : 503);
        }

        /** @var CareerActor $actor */
        $actor = $request->attributes->get(CareerActor::class);

        return Inertia::render('Admin/RegistrationDetail', [
            'registration' => $registration,
            'canApprove' => $authorization->allows($actor, CareerCapability::RegistrationApprove),
            'canReject' => $authorization->allows($actor, CareerCapability::RegistrationReject),
        ]);
    }
}
