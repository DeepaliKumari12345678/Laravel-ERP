<?php

namespace App\Core;

use App\Core\Configuration\Configuration;
use App\Core\Mail\MailSettings;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class ErpServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/erp.php', 'erp');
        $this->app->alias(Configuration::class, 'erp.config');
    }

    public function boot(): void
    {
        try {
            MailSettings::apply();
        } catch (\Throwable) {
            // DB may not be ready during early install
        }

        ResetPassword::createUrlUsing(function (object $user, string $token) {
            return url(route('admin.password.reset', [
                'token' => $token,
                'email' => $user->email,
            ], false));
        });

        Route::middleware(['web', 'erp.admin'])
            ->prefix(config('erp.admin_prefix', 'admin'))
            ->as('admin.')
            ->group(base_path('routes/admin.php'));

        Route::middleware('web')
            ->as('front.')
            ->group(base_path('routes/front.php'));

        Route::middleware('api')
            ->prefix('api')
            ->as('api.')
            ->group(base_path('routes/api.php'));
    }
}
