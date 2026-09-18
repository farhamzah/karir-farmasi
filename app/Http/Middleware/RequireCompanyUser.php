<?php

namespace App\Http\Middleware;

use App\Models\CompanyUser;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireCompanyUser
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $userId = $request->session()->get('company_user_id');
        $user = is_int($userId) || ctype_digit((string) $userId)
            ? CompanyUser::query()->with('company')->find($userId)
            : null;

        if ($user === null || ! $user->active || ! $user->company?->active) {
            $request->session()->forget('company_user_id');

            return redirect()->route('home');
        }

        $request->attributes->set(CompanyUser::class, $user);

        return $next($request);
    }
}
