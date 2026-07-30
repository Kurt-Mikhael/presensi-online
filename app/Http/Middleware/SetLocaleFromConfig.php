<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocaleFromConfig
{
    public function handle(Request $request, Closure $next): Response
    {
        app()->setLocale(config('app.locale', 'id'));

        return $next($request);
    }
}