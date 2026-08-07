<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class PasswordController extends Controller
{
    public function edit(): View
    {
        return view('auth.password');
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = $request->user();
        $user->update([
            'password' => $data['password'],
            'must_change_password' => false,
            'password_changed_at' => now(),
            'remember_token' => null,
        ]);

        $user->refresh();
        $request->session()->put('password_version', $user->password_changed_at?->getTimestamp());

        // Keep the current session, but invalidate remember-me tokens and rotate its ID.
        $request->session()->regenerate();

        return redirect()->route($user->isAdmin() ? 'admin.attendance.index' : 'attendance.index')
            ->with('status', 'Password berhasil diubah.');
    }
}
