<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\CompanyUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class CompanyAuthController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Company/Register');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'legal_name' => ['required', 'string', 'max:255'], 'display_name' => ['required', 'string', 'max:255'],
            'business_sector' => ['required', 'string', 'max:255'], 'company_size' => ['nullable', 'in:1-10,11-50,51-200,201-500,500+'],
            'website' => ['nullable', 'url:http,https', 'max:2048'], 'city' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:3000'], 'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:company_users,email'],
            'password' => ['required', 'confirmed', Password::min(10)->letters()->numbers()],
        ]);
        $user = DB::transaction(function () use ($data): CompanyUser {
            $data['email'] = mb_strtolower(trim($data['email']));
            $company = Company::query()->create(collect($data)->only(['legal_name', 'display_name', 'business_sector', 'company_size', 'website', 'city', 'description'])->all());

            return $company->users()->create(collect($data)->only(['name', 'email', 'password'])->all() + ['role' => 'company_admin']);
        });
        $request->session()->regenerate();
        $request->session()->put('company_user_id', $user->id);

        return redirect()->route('company.dashboard')->with('success', 'Pendaftaran diterima. Admin Karir akan meninjau data perusahaan.');
    }

    public function login(): Response
    {
        return Inertia::render('Company/Login');
    }

    public function authenticate(Request $request): RedirectResponse
    {
        $data = $request->validate(['email' => ['required', 'email'], 'password' => ['required', 'string']]);
        $user = CompanyUser::query()->with('company')->where('email', mb_strtolower(trim($data['email'])))->first();
        if ($user === null || ! $user->active || ! $user->company?->active || ! Hash::check($data['password'], $user->password)) {
            return back()->withErrors(['email' => 'Email atau kata sandi perusahaan tidak sesuai.'])->onlyInput('email');
        }
        $user->update(['last_login_at' => now()]);
        $request->session()->regenerate();
        $request->session()->put('company_user_id', $user->id);

        return redirect()->route('company.dashboard');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->session()->forget('company_user_id');
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
