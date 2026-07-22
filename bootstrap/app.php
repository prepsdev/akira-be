<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Console\Scheduling\Schedule;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \Illuminate\Http\Middleware\HandleCors::class,
        ]);
        $middleware->api(prepend: [
            \Illuminate\Http\Middleware\HandleCors::class,
        ]);

        $middleware->redirectGuestsTo(fn($request) => $request->expectsJson() ? null : route('login'));
    })
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->command('exports:send-scheduled daily')
            ->dailyAt('17:55')
            ->timezone('Asia/Jakarta');

        $schedule->command('exports:send-scheduled weekly')
            ->weeklyOn(1, '06:00')
            ->timezone('Asia/Jakarta');

        $schedule->command('exports:send-scheduled monthly')
            ->monthlyOn(1, '06:00')
            ->timezone('Asia/Jakarta');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated.'
                ], 401);
            }
        });
    })->create();
