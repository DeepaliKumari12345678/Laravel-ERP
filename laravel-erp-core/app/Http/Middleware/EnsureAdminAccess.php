<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->routeIs(
            'admin.login',
            'admin.login.store',
            'admin.signup',
            'admin.signup.store',
            'admin.password.request',
            'admin.password.email',
            'admin.password.reset',
            'admin.password.update',
        )) {
            return $next($request);
        }

        if (! auth()->check()) {
            return redirect()->route('admin.login');
        }

        $user = auth()->user();

        if (! $user->active || ! $user->isEmployee() || ! $user->employee?->active) {
            auth()->logout();

            return redirect()->route('admin.login')->withErrors([
                'email' => 'You do not have access to the back office.',
            ]);
        }

        return $next($request);
    }
}
