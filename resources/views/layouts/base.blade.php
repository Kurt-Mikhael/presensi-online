<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="color-scheme" content="light">
    <meta name="theme-color" content="#166534">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Presensi') · Presensi Online</title>
    <meta name="description" content="Aplikasi presensi masuk dan pulang dengan validasi lokasi.">

    <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
    <link rel="icon" href="{{ asset('icons/icon-192.png') }}" type="image/png">
    <link rel="apple-touch-icon" href="{{ asset('icons/icon-192.png') }}">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="Presensi">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
    @stack('head')
</head>
<body class="min-h-dvh bg-slate-50 text-slate-900 antialiased" data-loc-timeout="{{ config('attendance.location_timeout_ms') }}" data-max-age="{{ config('attendance.max_location_age_seconds') * 1000 }}">
    @yield('body')
    @stack('scripts')
    <script>
        // Mulai Alpine setelah seluruh module halaman selesai dimuat.
        window.addEventListener('load', () => {
            if (window.Alpine && !window.__alpineReady) {
                window.__alpineReady = true;
                window.Alpine.start();
            }
        });
    </script>
</body>
</html>