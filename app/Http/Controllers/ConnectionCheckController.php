<?php

namespace App\Http\Controllers;

class ConnectionCheckController extends Controller
{
    /**
     * GET /api/connection-check
     * Endpoint paling ringan untuk memeriksa apakah server dapat dihubungi.
     */
    public function __invoke()
    {
        return response()->json([
            'success' => true,
            'server_time' => now()->toIso8601String(),
        ]);
    }
}