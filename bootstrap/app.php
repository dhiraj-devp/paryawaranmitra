<?php

use App\Http\Middleware\EnsurePermission;
use App\Http\Middleware\EnsurePortal;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetLocale;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->command('follow-ups:remind')->everyMinute()->withoutOverlapping();
    })
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(SecurityHeaders::class);
        $middleware->web(append: [
            SetLocale::class,
        ]);

        $middleware->alias([
            'portal' => EnsurePortal::class,
            'permission' => EnsurePermission::class,
        ]);

        $middleware->redirectGuestsTo(function (Request $request) {
            if ($request->is('admin') || $request->is('admin/*')) {
                return route('admin.login');
            }

            if ($request->is('staff') || $request->is('staff/*')) {
                return route('staff.login');
            }

            return route('home');
        });

        // A browser may hold an admin session and a staff session together.
        // Send each login page back to the dashboard for the area being opened.
        $middleware->redirectUsersTo(function (Request $request) {
            if ($request->is('staff') || $request->is('staff/*')) {
                return route('staff.dashboard');
            }

            if ($request->is('admin') || $request->is('admin/*')) {
                return route('admin.dashboard');
            }

            if ($request->user('admin')) {
                return route('admin.dashboard');
            }

            if ($request->user('staff')) {
                return route('staff.dashboard');
            }

            return route('home');
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
