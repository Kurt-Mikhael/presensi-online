<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminUserController extends Controller
{
    public function index(Request $request): View
    {
        $users = User::query()
            ->whereIn('role', ['employee', 'admin'])
            ->when($request->input('q'), function ($query, $term) {
                $term = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $term).'%';
                $query->where(function ($userQuery) use ($term) {
                    $userQuery->where('name', 'ilike', $term)
                        ->orWhere('employee_number', 'ilike', $term);
                });
            })
            ->orderBy('role')
            ->orderBy('name')
            ->get();

        return view('admin.users', [
            'users' => $users,
            'search' => $request->input('q'),
        ]);
    }

    public function updateRole(Request $request, User $user): RedirectResponse
    {
        abort_if($user->isSuperAdmin() || $user->is($request->user()), 403, 'Role superadmin tidak dapat diubah.');

        $data = $request->validate([
            'role' => ['required', 'in:employee,admin'],
        ]);

        $user->update(['role' => $data['role']]);

        return back()->with('status', 'Role pengguna berhasil diperbarui.');
    }
}
