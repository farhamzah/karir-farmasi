<?php

namespace App\Http\Middleware;

use App\Models\CompanyUser;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireVerifiedCompany
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var CompanyUser|null $user */
        $user = $request->attributes->get(CompanyUser::class);

        if ($user === null || ! $user->company->canSearchTalent()) {
            return redirect()->route('company.dashboard')->withErrors([
                'company' => 'Talent Search tersedia setelah perusahaan berstatus terverifikasi dan aktif.',
            ]);
        }

        return $next($request);
    }
}
