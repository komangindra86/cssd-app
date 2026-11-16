<?php

use App\Http\Controllers\AuthController;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\EnsureUserIsActive::class,
        ]);

        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
            'role' => \App\Http\Middleware\RoleMiddleware::class,
        ]);

        // User yang ternyata masih login di halaman login dikembalikan ke halaman asalnya.
        $middleware->redirectUsersTo(fn (Request $request) => AuthController::urlKembali($request) ?? route('dashboard'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Token CSRF tidak cocok karena sesi sudah habis (mis. tombol Logout atau form lama): kembali ke login.
        // Laravel sudah mengubah TokenMismatchException menjadi HttpException 419 sebelum handler ini dipanggil.
        $exceptions->render(function (HttpException $e, Request $request) {
            if ($e->getPrevious() instanceof TokenMismatchException && !$request->expectsJson()) {
                return redirect()->route('login', ['sesi' => 'berakhir']);
            }
        });
    })->create();
