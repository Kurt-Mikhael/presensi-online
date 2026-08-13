<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\MasterEmployee;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function showLogin(Request $request)
    {
        if (Auth::check()) {
            return redirect()->intended($this->redirectFor(Auth::user()));
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $localUser = User::query()
            ->where('auth_source', 'local')
            ->where('is_active', true)
            ->where(function ($query) use ($credentials) {
                $query->where('username', $credentials['login'])
                    ->orWhere('email', $credentials['login']);
            })
            ->first();

        if ($localUser && Hash::check($credentials['password'], (string) $localUser->password)) {
            Auth::login($localUser, $request->boolean('remember'));
            $user = $localUser;
        } else {
            $masterEmployee = MasterEmployee::query()
                ->where(function ($query) use ($credentials) {
                    $query->where('nik', $credentials['login'])
                        ->orWhere('username', $credentials['login']);
                })
                ->first();

            if (! $masterEmployee || ! Hash::check($credentials['password'], (string) $masterEmployee->password)) {
                throw ValidationException::withMessages([
                    'login' => __('Login gagal. Periksa kembali kredensial Anda.'),
                ]);
            }

            $user = User::query()->firstOrCreate(
                ['employee_number' => (string) $masterEmployee->nik],
                [
                    'name' => $masterEmployee->nama,
                    'username' => $masterEmployee->username,
                    'role' => 'employee',
                    'is_active' => true,
                    'attendance_required' => true,
                    'password' => null,
                    'auth_source' => 'master',
                ],
            );

            $user->forceFill([
                'name' => $masterEmployee->nama,
                'username' => $masterEmployee->username,
                'is_active' => true,
                'auth_source' => 'master',
                'last_login_at' => now(),
            ])->save();

            Auth::login($user, $request->boolean('remember'));
        }

        $request->session()->regenerate();
        if ($user->auth_source === 'local') {
            $user->update(['last_login_at' => now()]);
        }

        return redirect()->intended($this->redirectFor(Auth::user()));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    protected function redirectFor($user): string
    {
        return $user->isAdmin() ? route('admin.attendance.index') : route('attendance.index');
    }
}
