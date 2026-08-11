<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withSchedule(function (\Illuminate\Console\Scheduling\Schedule $schedule): void {
        $schedule->command('subscriptions:send-renewal-reminders --days=7')->dailyAt('08:00');
    })
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->group('universal', []);
        $middleware->alias([
            'admin.auth' => \App\Http\Middleware\AdminAuth::class,
            'admin.permission' => \App\Http\Middleware\AdminPermission::class,
            'tenant.storefront.context' => \App\Http\Middleware\ApplyStorefrontSessionContext::class,
            'tenant.permission' => \App\Http\Middleware\TenantPermission::class,
            'tenant.gateway.blocked' => \App\Http\Middleware\CheckTenantGatewayBlocked::class,
            'preview.template' => \App\Http\Middleware\ForcePreviewTemplate::class,
            'owner.auth' => \App\Http\Middleware\TenantOwnerAuth::class,
        ]);

        $middleware->web([
            \App\Http\Middleware\SetLocale::class,
            \App\Http\Middleware\CheckMaintenanceWindow::class,
            // \Stancl\Tenancy\Middleware\PreventAccessFromTenantDomains::class,

        ]);

        $middleware->trustHosts(at: fn() => ['.*']);


        // $middleware->priority([
        //     \Stancl\Tenancy\Middleware\PreventAccessFromTenantDomains::class,
        //     \Stancl\Tenancy\Middleware\InitializeTenancy::class,
        // ]);

        // Redirect unauthenticated .auth:storefront. requests to the tenant storefront login
        $middleware->redirectGuestsTo(function (\Illuminate\Http\Request $request) {
            if ($request->is('admin*')) {
                return route('tenant.login');
            }

            return route('tenant.storefront.login');
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Stancl\Tenancy\Exceptions\TenantCouldNotBeIdentifiedOnDomainException $e, \Illuminate\Http\Request $request) {
            return redirect(config('app.url') . '?error=' . urlencode('This store does not exist.'));
        });
    })->create();
