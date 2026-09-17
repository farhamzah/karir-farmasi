<?php

namespace App\Http\Controllers\Admin;

use App\Data\CareerActor;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Operational\InAppNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CompanyController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Companies/Index', ['companies' => Company::query()->withCount('users')->latest()->get()->map(fn ($company) => [
            'reference' => $company->public_reference, 'name' => $company->display_name, 'legal_name' => $company->legal_name,
            'sector' => $company->business_sector, 'city' => $company->city, 'status' => $company->verification_status,
            'active' => $company->active, 'users_count' => $company->users_count,
        ])->all()]);
    }

    public function update(Request $request, string $reference, InAppNotification $notifications): RedirectResponse
    {
        $company = Company::query()->where('public_reference', $reference)->firstOrFail();
        $data = $request->validate(['verification_status' => ['required', Rule::in(['pending', 'verified', 'rejected', 'suspended'])], 'decision_note' => ['nullable', 'string', 'max:2000']]);
        /** @var CareerActor $actor */
        $actor = $request->attributes->get(CareerActor::class);
        $status = $data['verification_status'];
        $company->update($data + ['verified_at' => $status === 'verified' ? now() : null, 'verified_by_core_user_id' => $status === 'verified' ? $actor->coreUserId : null]);
        DB::table('audit_events')->insert(['event_type' => 'company.verification.'.$status, 'actor_reference' => $actor->coreUserId,
            'metadata' => json_encode(['company_reference' => $company->public_reference, 'status' => $status], JSON_THROW_ON_ERROR), 'created_at' => now(), 'updated_at' => now()]);
        $company->users()->get()->each(fn ($user) => $notifications->send('company_user', $user->public_reference, 'company.verification.decision',
            'Status verifikasi perusahaan', "Status perusahaan kini {$status}.", '/company/dashboard', ['status' => $status]));

        return back()->with('success', 'Status verifikasi perusahaan diperbarui.');
    }
}
