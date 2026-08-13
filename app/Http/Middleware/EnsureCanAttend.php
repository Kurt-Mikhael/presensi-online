<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCanAttend
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->isSuperAdmin()) {
            if ($request->expectsJson() || $request->is('api/*')) {
                abort(403, 'Superadmin tidak dapat mengisi presensi.');
            }

            return redirect()->route('admin.attendance.index');
        }

        if (! in_array($request->user()?->role, ['employee', 'admin'], true)) {
            abort(403, 'Superadmin tidak dapat mengisi presensi.');
        }

        return $next($request);
    }
}
