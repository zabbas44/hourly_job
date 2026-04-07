<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfSchedulerAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->session()->get('scheduler_authenticated', false)) {
            return redirect()->route('dashboard');
        }

        return $next($request);
    }
}
