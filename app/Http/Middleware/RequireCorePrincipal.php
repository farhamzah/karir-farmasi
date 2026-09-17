<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireCorePrincipal
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! is_array($request->session()->get('core_principal'))) {
            return redirect()->route('login')->withErrors(['identifier' => 'Silakan login untuk melanjutkan.']);
        }

        return $next($request);
    }
}
