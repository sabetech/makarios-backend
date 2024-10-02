<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->api(prepend: [
            \App\Http\Middleware\ApiAuthenticate::class,
        ]);

        $middleware->redirectGuestsTo(function (Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'ok' => false,
                    'message' => "In withMiddleware: Custom Unauthenticated message because no accept header!",
                    'msg' => "In withMiddleware: Custom Unauthenticated message because no accept header!",
                ], 401);
            } else {
                return route('/');
            }
        });
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'ok' => false,
                    'msg' => $e->getMessage(),
                    'message' => $e->getMessage(),
                ], 401);
            }
        });
    })->create();
