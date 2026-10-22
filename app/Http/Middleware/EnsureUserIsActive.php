<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Sesi user yang dinonaktifkan admin harus berakhir pada request berikutnya.
        if ($user && !$user->is_active) {
            Auth::logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->expectsJson()) {
                abort(401, 'User tidak aktif. Hubungi admin.');
            }

            return redirect()->route('login')
                ->withErrors(['email' => 'User tidak aktif. Hubungi admin.']);
        }

        return $next($request);
    }
}
