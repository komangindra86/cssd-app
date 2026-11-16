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
                // Halaman akan dialihkan ke login oleh JavaScript; pesannya ditampilkan di sana.
                $request->session()->flash('pesan_login', 'User tidak aktif. Hubungi admin.');

                return response()->json(['message' => 'User tidak aktif. Hubungi admin.'], 401);
            }

            return redirect()->route('login')
                ->withErrors(['email' => 'User tidak aktif. Hubungi admin.']);
        }

        return $next($request);
    }
}
