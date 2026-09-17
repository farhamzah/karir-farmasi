<?php

namespace App\Http\Controllers;

use App\Contracts\CoreAlumniGateway;
use App\Exceptions\CoreAlumniOperationFailed;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AlumniRegistrationController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Register');
    }

    public function store(Request $request, CoreAlumniGateway $gateway): RedirectResponse
    {
        $data = $request->validate([
            'student_number' => ['required', 'string', 'max:50'],
            'full_name' => ['required', 'string', 'max:255'],
            'claimed_program' => ['required', 'string', 'max:255'],
            'graduation_year' => ['nullable', 'integer', 'min:1950', 'max:2100'],
            'personal_email' => ['required', 'email', 'max:255'],
            'whatsapp' => ['required', 'string', 'max:30'],
            'password' => ['required', 'string', 'min:8', 'max:255', 'confirmed'],
        ]);

        unset($data['password_confirmation']);

        try {
            $registration = $gateway->register($data);
        } catch (CoreAlumniOperationFailed $exception) {
            return back()->withErrors($exception->errors ?: ['registration' => $exception->getMessage()])
                ->withInput($request->except(['password', 'password_confirmation']));
        }

        return redirect()->route('registration-status.show', $registration['reference'])
            ->with('success', 'Pendaftaran diterima dan menunggu verifikasi admin.');
    }
}
