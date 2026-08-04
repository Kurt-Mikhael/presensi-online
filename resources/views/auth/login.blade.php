@extends('layouts.guest')

@section('content')
<div class="grid min-h-dvh w-full grid-cols-1 lg:grid-cols-2">

    {{-- Brand panel (desktop) --}}
    <aside class="relative hidden overflow-hidden bg-brand-700 text-white lg:flex lg:flex-col">
        {{-- Image in normal proportions with fade overlay --}}
        <div class="absolute inset-0 overflow-hidden">
            <img src="{{ asset('proyek.webp') }}" alt="" aria-hidden="true"
                 class="absolute inset-0 h-full w-full object-cover">
            {{-- Blue fade overlay across the entire image --}}
            <div class="absolute inset-0" style="background: rgba(30, 79, 168, 0.55);"></div>
            <div class="absolute inset-0" style="background: linear-gradient(180deg, rgba(30, 79, 168, 0.30) 0%, rgba(18, 43, 89, 0.75) 100%);"></div>
        </div>

        <div class="relative z-10 flex h-full flex-col justify-between py-16 pl-16 pr-12">
            <div>
                <div class="flex items-center gap-2.5">
                    <span class="grid h-9 w-9 place-items-center rounded-lg bg-white/15 backdrop-blur-sm">
                        <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2"/><circle cx="12" cy="12" r="9.5"/></svg>
                    </span>
                    <span class="text-base font-semibold tracking-tight">Absensi <span class="text-white/60">Online</span></span>
                </div>

                <div class="mt-12" style="max-width: 380px;">
                    <div class="text-[11px] font-semibold uppercase tracking-[0.18em] text-white/90">PT Modern Widya Technical</div>
                    <h1 class="mt-3 text-[1.6rem] font-semibold leading-tight tracking-tight">
                        Sistem Absensi Karyawan
                    </h1>
                    <p class="mt-4 text-sm leading-relaxed text-white/90">
                        Catat kehadiran masuk dan pulang kerja dengan validasi lokasi area kerja.
                    </p>
                </div>
            </div>

            <div class="text-[11px] text-white/60">
                &copy; {{ date('Y') }} PT Modern Widya Technical
            </div>
        </div>
    </aside>

    {{-- Form --}}
    <section class="flex items-center justify-center px-5 py-10 sm:px-10 sm:py-12">
        <div class="w-full max-w-sm">

            <div class="mb-8 lg:hidden">
                <div class="flex items-center gap-2.5">
                    <span class="grid h-9 w-9 place-items-center rounded-lg bg-brand-600 text-white">
                        <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2"/><circle cx="12" cy="12" r="9.5"/></svg>
                    </span>
                    <span class="text-base font-semibold tracking-tight text-slate-900">Absensi <span class="text-slate-400">Online</span></span>
                </div>
                <div class="mt-1.5 text-[11px] font-semibold uppercase tracking-wide text-brand-700">PT Modern Widya Technical</div>
            </div>

            <header>
                <h2 class="text-2xl font-semibold tracking-tight text-slate-900">Selamat Datang</h2>
                <p class="mt-1.5 text-sm text-slate-500">Silakan masuk ke akun karyawan Anda</p>
            </header>

            @if ($errors->any())
            <div class="mt-5 rounded-lg border border-red-200 bg-red-50 px-3.5 py-2.5 text-sm text-red-800" role="alert">
                {{ $errors->first() }}
            </div>
            @endif

            <form method="post" action="{{ route('login') }}" class="mt-7 space-y-4">
                @csrf

                <div>
                    <label for="login" class="block text-sm font-medium text-slate-700">Username atau Email</label>
                    <div class="relative mt-1.5">
                        <span class="pointer-events-none absolute inset-y-0 left-0 grid w-10 place-items-center text-slate-400">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 7.5a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0"/></svg>
                        </span>
                        <input id="login" name="login" type="text" autocomplete="username" required autofocus
                               value="{{ old('login') }}"
                               class="block w-full rounded-lg border border-slate-300 bg-white py-2.5 pl-10 pr-3 text-[0.95rem] text-slate-900 placeholder:text-slate-400 focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-600/20">
                    </div>
                </div>

                <div>
                    <div class="flex items-center justify-between">
                        <label for="password" class="block text-sm font-medium text-slate-700">Password</label>
                        <a href="#" class="text-xs font-medium text-brand-700 hover:text-brand-800">Lupa Password?</a>
                    </div>
                    <div class="relative mt-1.5">
                        <span class="pointer-events-none absolute inset-y-0 left-0 grid w-10 place-items-center text-slate-400">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"/></svg>
                        </span>
                        <input id="password" name="password" type="password" autocomplete="current-password" required
                               class="block w-full rounded-lg border border-slate-300 bg-white py-2.5 pl-10 pr-10 text-[0.95rem] text-slate-900 placeholder:text-slate-400 focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-600/20">
                    </div>
                </div>

                <label class="flex items-center gap-2 text-sm text-slate-600">
                    <input type="checkbox" name="remember" value="1"
                           class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-600/30">
                    Ingat saya di perangkat ini
                </label>

                <button type="submit" class="mt-2 inline-flex w-full items-center justify-center gap-2 rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700 focus:outline-none focus:ring-4 focus:ring-brand-600/20">
                    Masuk
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
                </button>
            </form>

            <div class="mt-6 flex items-start gap-2.5 rounded-lg border border-slate-200 bg-slate-50 px-3.5 py-3 text-xs text-slate-600">
                <svg class="mt-0.5 h-4 w-4 flex-none text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z"/></svg>
                <p>Aplikasi ini memerlukan akses internet dan izin Lokasi (GPS) yang aktif untuk memverifikasi area absensi Anda.</p>
            </div>
        </div>
    </section>
</div>
@endsection
