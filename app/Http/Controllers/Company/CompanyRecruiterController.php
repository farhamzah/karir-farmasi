<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\CompanyUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class CompanyRecruiterController extends Controller
{
    public function index(Request $request): Response
    {
        $actor = $this->actor($request);
        abort_unless($actor->role === 'company_admin', 403);

        return Inertia::render('Company/Recruiters', ['members' => $actor->company->users()->orderBy('name')->get()->map(fn ($user) => [
            'reference' => $user->public_reference, 'name' => $user->name, 'email' => $user->email,
            'role' => $user->role, 'active' => $user->active, 'last_login_at' => $user->last_login_at?->toAtomString(),
        ])->all()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $actor = $this->actor($request);
        abort_unless($actor->role === 'company_admin', 403);
        $data = $request->validate(['name' => ['required', 'string', 'max:255'], 'email' => ['required', 'email', 'max:255', 'unique:company_users,email'],
            'password' => ['required', 'confirmed', Password::min(10)->letters()->numbers()]]);
        $data['email'] = mb_strtolower(trim($data['email']));
        $actor->company->users()->create($data + ['role' => 'recruiter']);

        return back()->with('success', 'Akun rekruter terpisah berhasil dibuat.');
    }

    public function update(Request $request, string $reference): RedirectResponse
    {
        $actor = $this->actor($request);
        abort_unless($actor->role === 'company_admin', 403);
        $target = $actor->company->users()->where('public_reference', $reference)->firstOrFail();
        abort_if($target->is($actor), 422, 'Akun yang sedang digunakan tidak dapat dinonaktifkan.');
        $target->update($request->validate(['active' => ['required', 'boolean']]));

        return back()->with('success', 'Status rekruter diperbarui.');
    }

    private function actor(Request $request): CompanyUser
    {
        return $request->attributes->get(CompanyUser::class);
    }
}
