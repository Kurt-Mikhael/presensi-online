@extends('layouts.base')

@section('body')
<div class="flex min-h-dvh" x-data="{ open: false }">
    {{-- Sidebar (desktop) / drawer (mobile) --}}
    <aside
        :class="open ? 'translate-x-0' : '-translate-x-full'"
        class="sidebar-shell fixed inset-y-0 left-0 z-40 flex w-72 max-w-[88vw] flex-col overflow-hidden border-r border-slate-900/10 shadow-2xl transition-transform duration-200 lg:static lg:translate-x-0 lg:shadow-none"
    >
        <div class="sidebar-shell-decor" aria-hidden="true"></div>

        {{-- Brand --}}
        <div class="relative flex items-center gap-3 px-5 pb-4 pt-5">
            <span class="sidebar-brand-chip grid h-11 w-11 place-items-center text-white">
                <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2"/>
                    <circle cx="12" cy="12" r="9.5"/>
                </svg>
            </span>
            <div class="min-w-0">
                <div class="flex items-center gap-2">
                    <div class="truncate text-sm font-semibold tracking-tight text-white">Absensi</div>
                </div>
                <div class="mt-0.5 text-[11px] text-white/60">Sistem Kehadiran</div>
            </div>

            <button type="button" x-on:click="open=false" class="ml-auto grid h-9 w-9 place-items-center rounded-full border border-white/10 bg-white/10 text-white/80 transition hover:bg-white/15 lg:hidden" aria-label="Tutup menu">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6 6 18"/></svg>
            </button>
        </div>

        {{-- Profile --}}
        <div class="relative mx-4 rounded-2xl border border-white/10 bg-white/8 px-4 py-3.5">
            <div class="flex items-center gap-3">
                <span class="grid h-11 w-11 place-items-center overflow-hidden rounded-full border border-white/10 bg-white/10 text-sm font-semibold text-white">
                    {{ strtoupper(mb_substr($user->name, 0, 1)) }}
                </span>
                <div class="min-w-0 flex-1">
                    <div class="truncate text-sm font-semibold text-white">{{ $user->name }}</div>
                    <div class="mt-0.5 text-[11px] text-white/60">{{ $user->isSuperAdmin() ? 'Super Administrator' : ($user->isAdmin() ? 'Administrator' : 'Karyawan') }}</div>
                </div>
            </div>
        </div>

        {{-- Nav --}}
        <nav class="relative flex-1 space-y-2 px-3 py-5">
            @if($user->isAdmin())
                <a href="{{ route('admin.location') }}" x-on:click="open=false"
                   class="sidebar-nav-item flex items-center gap-3 rounded-2xl px-3.5 py-3 text-sm font-medium {{ request()->routeIs('admin.location*') ? 'sidebar-nav-active' : 'text-white/72 hover:bg-white/8 hover:text-white' }}">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z"/></svg>
                    Pengaturan Area
                </a>
                <a href="{{ route('admin.attendance.index') }}" x-on:click="open=false"
                   class="sidebar-nav-item flex items-center gap-3 rounded-2xl px-3.5 py-3 text-sm font-medium {{ request()->routeIs('admin.attendance*') ? 'sidebar-nav-active' : 'text-white/72 hover:bg-white/8 hover:text-white' }}">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z"/></svg>
                     Daftar Absensi
                </a>
                @if($user->isSuperAdmin())
                    <a href="{{ route('admin.users.index') }}" x-on:click="open=false"
                       class="sidebar-nav-item flex items-center gap-3 rounded-2xl px-3.5 py-3 text-sm font-medium {{ request()->routeIs('admin.users*') ? 'sidebar-nav-active' : 'text-white/72 hover:bg-white/8 hover:text-white' }}">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2m9-11a4 4 0 1 0-8 0 4 4 0 0 0 8 0Zm5 3v6m3-3h-6"/></svg>
                        Kelola Pengguna
                    </a>
                @endif
                @if(! $user->isSuperAdmin())
                    <a href="{{ route('attendance.index') }}" x-on:click="open=false"
                       class="sidebar-nav-item flex items-center gap-3 rounded-2xl px-3.5 py-3 text-sm font-medium {{ request()->routeIs('attendance.*') ? 'sidebar-nav-active' : 'text-white/72 hover:bg-white/8 hover:text-white' }}">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12 12 3l9 9M5 10v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V10"/></svg>
                        Dashboard Presensi
                    </a>
                @endif
            @else
                <a href="{{ route('attendance.index') }}" x-on:click="open=false"
                   class="sidebar-nav-item flex items-center gap-3 rounded-2xl px-3.5 py-3 text-sm font-medium {{ request()->routeIs('attendance.*') ? 'sidebar-nav-active' : 'text-white/72 hover:bg-white/8 hover:text-white' }}">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12 12 3l9 9M5 10v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V10"/></svg>
                    Dashboard
                </a>
            @endif

            <form method="post" action="{{ route('logout') }}" class="sticky bottom-0 mt-auto border-t rounded-2xl border-white/10 bg-slate-950/45 px-3 py-3 backdrop-blur">
                @csrf
                <button type="submit" class=" flex w-full items-center gap-3 rounded-2xl px-3.5 py-3 text-sm font-medium text-white/75 transition hover:bg-white/8 hover:text-white">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9"/></svg>
                    Logout
                </button>
            </form>
        </nav>

    </aside>

    {{-- Backdrop for mobile drawer --}}
    <div x-show="open" x-on:click="open=false" class="fixed inset-0 z-30 bg-slate-950/55 backdrop-blur-[2px] lg:hidden" x-cloak></div>

    {{-- Main content --}}
    <div class="flex min-h-dvh min-w-0 flex-1 flex-col">
        {{-- Topbar (mobile only) --}}
        <header class="sticky top-0 z-20 flex h-14 items-center justify-between border-b border-slate-200 bg-white/95 px-4 backdrop-blur lg:hidden">
            <button type="button" x-on:click="open=!open" class="flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 shadow-sm" aria-label="Buka menu">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/></svg>
                Menu
            </button>
            <div class="flex items-center gap-2">
                <span class="grid h-7 w-7 place-items-center rounded-md bg-brand-600 text-white shadow-sm shadow-brand-600/20">
                    <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2"/><circle cx="12" cy="12" r="9.5"/></svg>
                </span>
                <span class="text-sm font-semibold text-slate-900">Absensi</span>
            </div>
            <div class="w-9"></div>
        </header>

        <main class="flex-1 px-4 py-5 sm:px-6 sm:py-6 lg:px-8 lg:py-8">
            @yield('content')
        </main>
    </div>
</div>
@endsection
