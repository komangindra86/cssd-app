<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        // Sesi habis di tengah halaman: setelah login kembali ke halaman yang tadi dibuka.
        if ($kembali = self::urlKembali($request)) {
            $request->session()->put('url.intended', $kembali);
        }

        return view('auth.login', [
            'sesiBerakhir' => $request->query('sesi') === 'berakhir',
        ]);
    }

    /**
     * Alamat kembali dari parameter ?kembali=, hanya path di aplikasi ini (bukan situs lain).
     */
    public static function urlKembali(Request $request): ?string
    {
        $path = $request->query('kembali');

        if (!is_string($path) || !preg_match('#^/(?![/\\\\])#', $path) || preg_match('#^/(login|logout)\b#', $path)) {
            return null;
        }

        return url($path);
    }

    public function prosesLogin(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $remember = $request->boolean('remember');

        if (!Auth::attempt($credentials, $remember)) {
            return back()
                ->withErrors(['email' => 'Email atau password salah.'])
                ->onlyInput('email');
        }

        if (!Auth::user()->is_active) {
            Auth::logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()
                ->withErrors(['email' => 'User tidak aktif. Hubungi admin.'])
                ->onlyInput('email');
        }

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
