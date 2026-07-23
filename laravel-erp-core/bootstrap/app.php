<?php

use App\Http\Middleware\EnsureAdminAccess;
use App\Http\Middleware\EnsurePermission;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            SetLocale::class,
        ]);

        $middleware->redirectGuestsTo(fn () => route('admin.login'));
        $middleware->redirectUsersTo(function () {
            $user = auth()->user();
            if (! $user) {
                return route('admin.login');
            }
            foreach (config('erp.menu', []) as $section) {
                foreach ($section['items'] as $item) {
                    if (! empty($item['children'])) {
                        foreach ($item['children'] as $child) {
                            if ($user->hasPermission($child['permission'])) {
                                return route($child['route']);
                            }
                        }

                        continue;
                    }

                    if (! empty($item['route']) && $user->hasPermission($item['permission'])) {
                        return route($item['route']);
                    }
                }
            }

            return route('admin.profile');
        });

        $middleware->alias([
            'erp.admin' => EnsureAdminAccess::class,
            'erp.permission' => EnsurePermission::class,
            'erp.webservice' => \App\Http\Middleware\AuthenticateWebservice::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
