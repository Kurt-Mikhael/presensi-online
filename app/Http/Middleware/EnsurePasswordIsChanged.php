<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordIsChanged
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user) {
            $passwordVersion = $user->password_changed_at?->getTimestamp();
            $sessionVersion = $request->session()->get('password_version');

            if ($sessionVersion !== null && $sessionVersion !== $passwordVersion) {
                auth()->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')->withErrors([
                    'login' => 'Sesi Anda berakhir karena password akun berubah. Silakan login kembali.',
                ]);
            }

            $request->session()->put('password_version', $passwordVersion);
        }

        if ($user?->must_change_password && ! $request->routeIs('password.edit', 'password.update', 'logout')) {
            return redirect()->route('password.edit');
        }

        return $next($request);
    }
}
