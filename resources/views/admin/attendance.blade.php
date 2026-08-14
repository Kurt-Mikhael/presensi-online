@extends('layouts.app', ['user' => auth()->user()])

@section('title', 'Daftar Absensi')

@section('content')
@php
    $total   = $stats['total'];
    $masuk   = $stats['masuk'];
    $pulang  = $stats['pulang'];
    $pending = $stats['pending'];
@endphp

<div class="space-y-5">
    <header class="flex flex-col gap-1">
        <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Daftar Absensi</h1>
        <p class="text-sm text-slate-500">Pantau catatan absensi seluruh karyawan berdasarkan periode.</p>
    </header>

    @if(session('status'))
        <div class="rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-800">{{ session('status') }}</div>
    @endif
    @if($errors->any())
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $errors->first() }}</div>
    @endif

    {{-- Filter --}}
    <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6">
        <form method="get" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_minmax(18rem,1.35fr)] lg:gap-5">
            <div class="min-w-0">
                <label for="date_from" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Dari tanggal</label>
                <div class="relative mt-1.5">
                    <span class="pointer-events-none absolute inset-y-0 left-0 grid w-10 place-items-center text-slate-400">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5"/></svg>
                    </span>
                    <input id="date_from" type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="block w-full rounded-lg border border-slate-300 bg-white py-2.5 pl-10 pr-3 text-sm text-slate-900 focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-600/20">
                </div>
            </div>
            <div class="min-w-0">
                <label for="date_to" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Sampai tanggal</label>
                <div class="relative mt-1.5">
                    <span class="pointer-events-none absolute inset-y-0 left-0 grid w-10 place-items-center text-slate-400">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5"/></svg>
                    </span>
                    <input id="date_to" type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="block w-full rounded-lg border border-slate-300 bg-white py-2.5 pl-10 pr-3 text-sm text-slate-900 focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-600/20">
                </div>
            </div>
            <div class="min-w-0 sm:col-span-2 lg:col-span-1">
                <label for="q" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Cari nama atau nomor pegawai</label>
                <div class="relative mt-1.5">
                    <span class="pointer-events-none absolute inset-y-0 left-0 grid w-10 place-items-center text-slate-400">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
                    </span>
                    <input id="q" type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Cari nama atau nomor pegawai…" class="block w-full rounded-lg border border-slate-300 bg-white py-2.5 pl-10 pr-3 text-sm text-slate-900 placeholder:text-slate-400 focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-600/20">
                </div>
            </div>
            <div class="flex flex-col gap-2 border-t border-slate-100 pt-4 sm:col-span-2 sm:flex-row sm:justify-end lg:col-span-3">
                <button type="submit" class="inline-flex min-h-11 w-full items-center justify-center gap-2 rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700 sm:w-auto">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
                    Cari
                </button>
                <button type="submit" formaction="{{ route('admin.attendance.export') }}" formtarget="_blank" class="btn-export inline-flex min-h-11 w-full items-center justify-center gap-2 rounded-lg px-4 py-2.5 text-sm font-semibold shadow-sm sm:w-auto">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v12m0 0 4-4m-4 4-4-4M5 21h14"/></svg>
                    Export Excel
                </button>
                <input type="hidden" name="view" value="{{ $filters['view'] }}">
                @foreach(['detail' => 'Laporan Detail', 'summary' => 'Laporan Ringkasan'] as $mode => $label)
                    <a href="{{ route('admin.attendance.index', array_merge(['date_from' => $filters['date_from'], 'date_to' => $filters['date_to'], 'q' => $filters['q']], ['view' => $mode])) }}" class="inline-flex min-h-11 w-full items-center justify-center rounded-lg border px-4 py-2.5 text-sm font-semibold transition sm:w-auto {{ $filters['view'] === $mode ? 'border-brand-600 bg-brand-50 text-brand-700' : 'border-slate-300 bg-white text-slate-700 hover:bg-slate-50' }}" @if($filters['view'] === $mode) aria-current="page" @endif>{{ $label }}</a>
                @endforeach
                <a href="{{ route('admin.attendance.index') }}" class="inline-flex min-h-11 w-full items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 sm:w-auto">Reset</a>
            </div>
        </form>
    </section>

    {{-- Stat cards --}}
    @if($filters['view'] === 'detail')
    <section class="grid grid-cols-2 gap-3 lg:grid-cols-4">
        <div class="rounded-2xl border border-slate-200 bg-white p-4">
            <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-slate-400">
                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9h16.5m-16.5 6.75h16.5"/><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 17.25h16.5"/></svg>
                Total
            </div>
            <div class="mt-2 flex items-baseline gap-1.5">
                <span class="text-2xl font-semibold tabular-nums text-slate-900">{{ $total }}</span>
                <span class="text-xs text-slate-500">Pegawai</span>
            </div>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4">
            <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-slate-400">
                <svg class="h-3.5 w-3.5 text-brand-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15M9 12l3-3m0 0 3 3m-3-3v12"/></svg>
                Masuk
            </div>
            <div class="mt-2 flex items-baseline gap-1.5">
                <span class="text-2xl font-semibold tabular-nums text-slate-900">{{ $masuk }}</span>
                <span class="text-xs text-slate-500">Pegawai</span>
            </div>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4">
            <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-slate-400">
                <svg class="h-3.5 w-3.5 text-blue-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15M9 12l3 3m0 0 3-3m-3 3V3"/></svg>
                Pulang
            </div>
            <div class="mt-2 flex items-baseline gap-1.5">
                <span class="text-2xl font-semibold tabular-nums text-slate-900">{{ $pulang }}</span>
                <span class="text-xs text-slate-500">Pegawai</span>
            </div>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4">
            <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-slate-400">
                <svg class="h-3.5 w-3.5 text-red-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/></svg>
                Pending
            </div>
            <div class="mt-2 flex items-baseline gap-1.5">
                <span class="text-2xl font-semibold tabular-nums text-slate-900">{{ $pending }}</span>
                <span class="text-xs text-slate-500">Pegawai</span>
            </div>
        </div>
    </section>
    @endif

    @if($filters['view'] === 'detail')
    {{-- Tabel (desktop) --}}
    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
        <div class="hidden overflow-x-auto md:block">
            <table class="w-full min-w-[1300px] border-collapse text-sm">
                <thead>
                    <tr class="border-b border-slate-100 text-left text-[11px] font-semibold uppercase tracking-wide text-slate-400">
                        <th rowspan="2" class="px-5 py-3">No. Pegawai</th>
                        <th rowspan="2" class="px-5 py-3">Nama Pegawai</th>
                        <th rowspan="2" class="px-5 py-3">Tanggal</th>
                        <th rowspan="2" class="px-5 py-3">Jam Masuk</th>
                        <th rowspan="2" class="px-5 py-3">Ketepatan Masuk</th>
                        <th rowspan="2" class="px-5 py-3">Jam Pulang</th>
                        <th rowspan="2" class="px-5 py-3">Durasi Kerja</th>
                        <th rowspan="2" class="px-5 py-3">Lembur</th>
                        <th colspan="2" class="px-3 py-2 text-center">Fase 1</th>
                        <th colspan="2" class="px-3 py-2 text-center">Fase 2</th>
                        <th colspan="2" class="px-3 py-2 text-center">Fase 3</th>
                        <th colspan="2" class="px-3 py-2 text-center">Fase 4</th>
                        <th rowspan="2" class="px-5 py-3">Status</th>
                        <th rowspan="2" class="px-5 py-3 text-right">Aksi</th>
                    </tr>
                    <tr class="border-b border-slate-100 text-center text-[10px] font-semibold uppercase tracking-wide text-slate-400">
                         <th class="px-3 py-1.5">Jam</th>
                         <th class="px-3 py-1.5">Menit</th>
                         <th class="px-3 py-1.5">Jam</th>
                         <th class="px-3 py-1.5">Menit</th>
                        <th class="px-3 py-1.5">Jam</th>
                        <th class="px-3 py-1.5">Menit</th>
                        <th class="px-3 py-1.5">Jam</th>
                        <th class="px-3 py-1.5">Menit</th>
                    </tr>
                </thead>
                @forelse($records as $r)
                        @php
                            $user = $r->user;
                             $ci = $r->check_in_at?->setTimezone(config('app.timezone'));
                             $co = $r->check_out_at?->setTimezone(config('app.timezone'));
                             $isWeekend = $r->attendance_date?->isWeekend();
                             $phases = $r->overtime_phases;
                             $isLate = $ci && $ci->format('H:i') > $workSettings->work_start;
                        @endphp
                <tbody x-data="{ photoOpen: false, editOpen: false }" class="divide-y divide-slate-100">
                        <tr class="align-top">
                            <td class="px-5 py-3.5 font-mono text-xs text-slate-500">{{ $user?->employee_number ?? '—' }}</td>
                            <td class="px-5 py-3.5 font-semibold text-slate-900">{{ $user?->name ?? '—' }}</td>
                            <td class="px-5 py-3.5 text-slate-700">{{ $r->attendance_date?->translatedFormat('l, j M Y') }}</td>
                            <td class="px-5 py-3.5 font-mono tabular-nums text-slate-700">{{ $ci?->format('H:i') ?? '—' }}</td>
                            <td class="px-5 py-3.5">
                                @if(! $ci)
                                    <span class="text-slate-400">—</span>
                                @elseif($isLate)
                                    <span class="inline-flex rounded-md bg-red-50 px-2 py-0.5 text-xs font-semibold text-red-700">Terlambat</span>
                                @else
                                    <span class="inline-flex rounded-md bg-emerald-50 px-2 py-0.5 text-xs font-semibold text-emerald-700">Tepat Waktu</span>
                                @endif
                            </td>
                            <td class="px-5 py-3.5 font-mono tabular-nums text-slate-700">{{ $co?->format('H:i') ?? '—' }}</td>
                            <td class="px-5 py-3.5 font-mono tabular-nums text-slate-700">{{ $r->work_duration ?? '—' }}</td>
                            <td class="px-5 py-3.5 text-slate-700">
                                <div class="font-mono tabular-nums">{{ $r->overtime_duration ?? '-' }}</div>
                            </td>
                            @foreach([0, 1, 2, 3] as $phaseIndex)
                                @php $phaseMinutes = $phases[$phaseIndex]['minutes'] ?? 0; @endphp
                                <td class="px-3 py-3.5 text-center font-mono tabular-nums text-slate-700">{{ intdiv($phaseMinutes, 60) }}</td>
                                <td class="px-3 py-3.5 text-center font-mono tabular-nums text-slate-700">{{ $phaseMinutes % 60 }}</td>
                            @endforeach
                            <td class="px-5 py-3.5">
                                @if($isWeekend && ($r->check_in_at || $r->check_out_at))
                                    <span class="inline-flex items-center gap-1.5 rounded-md bg-purple-50 px-2 py-0.5 text-xs font-semibold text-purple-700">Lembur</span>
                                @elseif($isWeekend)
                                    <span class="inline-flex items-center gap-1.5 rounded-md bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-600">Hari Libur</span>
                                @elseif($r->check_in_at && $r->check_out_at)
                                    <span class="inline-flex items-center gap-1.5 rounded-md bg-blue-50 px-2 py-0.5 text-xs font-semibold text-blue-700">
                                        <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                        Lengkap
                                    </span>
                                @elseif($r->check_in_at)
                                    <span class="inline-flex items-center gap-1.5 rounded-md bg-amber-50 px-2 py-0.5 text-xs font-semibold text-amber-700">
                                        <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                                        Sudah Masuk
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 rounded-md bg-red-50 px-2 py-0.5 text-xs font-semibold text-red-700">
                                        <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 0 0 5.636 5.636m12.728 12.728A9 9 0 0 1 5.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                        Belum Absensi
                                    </span>
                                @endif
                            </td>
                             <td class="px-5 py-3.5 text-right">
                                 @if($r->check_in_photo_url)
                                    <button type="button" @click="photoOpen = !photoOpen" class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-600 transition hover:border-brand-200 hover:bg-brand-50 hover:text-brand-700" :class="photoOpen ? 'border-brand-200 bg-brand-50 text-brand-700' : ''" aria-label="Lihat foto">
                                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
                                         <span x-text="photoOpen ? 'Tutup Foto' : 'Lihat Foto'">Lihat Foto</span>
                                    </button>
                                 @else
                                     <span class="text-xs text-slate-400">Belum ada foto</span>
                                 @endif
                                 @if(auth()->user()->isSuperAdmin())
                                     <button type="button" @click="editOpen = !editOpen" class="ml-2 inline-flex items-center gap-1.5 rounded-full border border-brand-200 px-3 py-1.5 text-xs font-semibold text-brand-700 transition hover:bg-brand-50">
                                          <span x-text="editOpen ? 'Tutup Koreksi' : 'Koreksi Waktu'">Koreksi Waktu</span>
                                     </button>
                                 @endif
                             </td>
                         </tr>
                         @if(auth()->user()->isSuperAdmin())
                             <tr x-show="editOpen" x-cloak>
                                  <td colspan="18" class="border-t border-brand-100 bg-brand-50/40 px-5 py-4">
                                     <form method="post" action="{{ route('admin.attendance.times', [$user, $r->attendance_date?->format('Y-m-d')]) }}" class="grid gap-3 lg:grid-cols-[1fr_1fr_1.4fr_auto] lg:items-end">
                                         @csrf
                                         @method('PATCH')
                                         <div>
                                             <label class="block text-[10px] font-semibold uppercase tracking-wide text-slate-500">Jam masuk</label>
                                             <input type="datetime-local" name="check_in_at" value="{{ $r->check_in_at?->setTimezone(config('app.timezone'))->format('Y-m-d\\TH:i') }}" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs">
                                         </div>
                                         <div>
                                             <label class="block text-[10px] font-semibold uppercase tracking-wide text-slate-500">Jam pulang</label>
                                             <input type="datetime-local" name="check_out_at" value="{{ $r->check_out_at?->setTimezone(config('app.timezone'))->format('Y-m-d\\TH:i') }}" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs">
                                         </div>
                                         <div>
                                             <label class="block text-[10px] font-semibold uppercase tracking-wide text-slate-500">Catatan koreksi</label>
                                             <input type="text" name="correction_note" placeholder="Contoh: Lupa absensi masuk" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs">
                                         </div>
                                         <div class="flex flex-wrap gap-2">
                                             <button type="submit" name="action" value="save" class="rounded-lg bg-brand-600 px-3 py-2 text-xs font-semibold text-white hover:bg-brand-700">Simpan</button>
                                             <button type="submit" name="action" value="cancel_check_in" class="rounded-lg border border-red-200 bg-white px-3 py-2 text-xs font-semibold text-red-700 hover:bg-red-50">Batalkan Masuk</button>
                                             <button type="submit" name="action" value="cancel_check_out" class="rounded-lg border border-red-200 bg-white px-3 py-2 text-xs font-semibold text-red-700 hover:bg-red-50">Batalkan Pulang</button>
                                         </div>
                                     </form>
                                 </td>
                             </tr>
                         @endif
                        <tr x-show="photoOpen" x-cloak>
                               <td colspan="18" class="bg-slate-50 px-5 py-4">
                                <div class="grid gap-4 lg:grid-cols-[220px_1fr]">
                                    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
                                        @if($r->check_in_photo_url)
                                             <img x-bind:src="photoOpen ? '{{ $r->check_in_photo_url }}' : null" loading="lazy" alt="Foto absensi {{ $user?->name ?? 'pegawai' }}" class="h-56 w-full object-cover">
                                        @else
                                            <div class="grid h-56 place-items-center text-sm text-slate-400">Tidak ada foto tersimpan.</div>
                                        @endif
                                    </div>
                                    <div class="flex items-center">
                                        <div>
                                            <div class="text-sm font-semibold text-slate-900">Foto absensi {{ $user?->name ?? 'pegawai' }}</div>
                                            <div class="mt-1 text-sm text-slate-500">{{ $r->check_in_photo_taken_at?->setTimezone(config('app.timezone'))?->format('d M Y, H:i') ?? 'Waktu foto tidak tersedia' }}</div>
                                            <div class="mt-2 text-xs text-slate-400">Klik tombol lihat foto lagi untuk menutup dan kembali ke tampilan tabel biasa.</div>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                </tbody>
                    @empty
                <tbody>
                    <tr><td colspan="18" class="px-5 py-10 text-center text-sm text-slate-400">Tidak ada catatan absensi pada filter ini.</td></tr>
                </tbody>
                    @endforelse
            </table>
        </div>

        {{-- Kartu (mobile) --}}
        <div class="divide-y divide-slate-100 md:hidden">
            @forelse($records as $r)
                @php
                    $user = $r->user;
                     $ci = $r->check_in_at?->setTimezone(config('app.timezone'));
                     $co = $r->check_out_at?->setTimezone(config('app.timezone'));
                     $isLate = $ci && $ci->format('H:i') > $workSettings->work_start;
                        @endphp
                <div x-data="{ photoOpen: false }" class="space-y-2.5 px-4 py-3.5">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <div class="truncate text-sm font-semibold text-slate-900">{{ $user?->name ?? '—' }}</div>
                            <div class="font-mono text-xs text-slate-500">{{ $user?->employee_number ?? '—' }}</div>
                            <div class="mt-0.5 text-xs text-slate-500">{{ $r->attendance_date?->translatedFormat('l, j M Y') }}</div>
                        </div>
                        <div class="flex-none">
                            @if($r->attendance_date?->isWeekend() && ($r->check_in_at || $r->check_out_at))
                                <span class="inline-flex items-center gap-1.5 rounded-md bg-purple-50 px-2 py-0.5 text-xs font-semibold text-purple-700">Lembur</span>
                            @elseif($r->attendance_date?->isWeekend())
                                <span class="inline-flex items-center gap-1.5 rounded-md bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-600">Hari Libur</span>
                            @elseif($r->check_in_at && $r->check_out_at)
                                <span class="inline-flex items-center gap-1.5 rounded-md bg-blue-50 px-2 py-0.5 text-xs font-semibold text-blue-700">
                                    <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                    Lengkap
                                </span>
                            @elseif($r->check_in_at)
                                <span class="inline-flex items-center gap-1.5 rounded-md bg-amber-50 px-2 py-0.5 text-xs font-semibold text-amber-700">
                                    <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                                    Sudah Masuk
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 rounded-md bg-red-50 px-2 py-0.5 text-xs font-semibold text-red-700">
                                    <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 0 0 5.636 5.636m12.728 12.728A9 9 0 0 1 5.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                    Belum Absensi
                                </span>
                            @endif
                        </div>
                    </div>
                    <div class="flex items-center justify-between gap-2">
                         <div class="text-xs text-slate-400">Foto absensi masuk</div>
                        @if($r->check_in_photo_url)
                            <button type="button" @click="photoOpen = !photoOpen" class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-600 transition hover:border-brand-200 hover:bg-brand-50 hover:text-brand-700" :class="photoOpen ? 'border-brand-200 bg-brand-50 text-brand-700' : ''">
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
                                 <span x-text="photoOpen ? 'Tutup Foto' : 'Lihat Foto'">Lihat Foto</span>
                            </button>
                         @else
                             <span class="text-xs text-slate-400">Belum ada foto</span>
                         @endif
                         @if(auth()->user()->isSuperAdmin())
                             <details class="mt-2">
                                 <summary class="cursor-pointer text-xs font-semibold text-brand-700">Koreksi waktu</summary>
                                 <form method="post" action="{{ route('admin.attendance.times', [$user, $r->attendance_date?->format('Y-m-d')]) }}" class="mt-2 space-y-2 rounded-lg border border-slate-200 bg-slate-50 p-2">
                                     @csrf
                                     @method('PATCH')
                                     <input type="datetime-local" name="check_in_at" value="{{ $r->check_in_at?->setTimezone(config('app.timezone'))->format('Y-m-d\\TH:i') }}" class="w-full rounded-md border border-slate-300 px-2 py-1 text-xs">
                                     <input type="datetime-local" name="check_out_at" value="{{ $r->check_out_at?->setTimezone(config('app.timezone'))->format('Y-m-d\\TH:i') }}" class="w-full rounded-md border border-slate-300 px-2 py-1 text-xs">
                                     <input type="text" name="correction_note" placeholder="Catatan (opsional)" class="w-full rounded-md border border-slate-300 px-2 py-1 text-xs">
                                     <div class="grid grid-cols-3 gap-1.5">
                                         <button type="submit" name="action" value="save" class="rounded-md bg-brand-600 px-2 py-1.5 text-xs font-semibold text-white">Simpan</button>
                                         <button type="submit" name="action" value="cancel_check_in" class="rounded-md border border-red-200 bg-white px-2 py-1.5 text-xs font-semibold text-red-700">Batalkan Masuk</button>
                                         <button type="submit" name="action" value="cancel_check_out" class="rounded-md border border-red-200 bg-white px-2 py-1.5 text-xs font-semibold text-red-700">Batalkan Pulang</button>
                                     </div>
                                 </form>
                             </details>
                         @endif
                     </div>
                    <div class="grid grid-cols-2 gap-2 text-xs">
                        <div class="rounded-lg border border-slate-200 bg-slate-50/60 px-3 py-2">
                            <div class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">Jam Masuk</div>
                            <div class="mt-0.5 font-mono font-semibold tabular-nums text-slate-900">{{ $ci?->format('H:i') ?? '—' }}</div>
                        </div>
                        <div class="rounded-lg border border-slate-200 bg-slate-50/60 px-3 py-2">
                            <div class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">Jam Pulang</div>
                            <div class="mt-0.5 font-mono font-semibold tabular-nums text-slate-900">{{ $co?->format('H:i') ?? '—' }}</div>
                        </div>
                        <div class="rounded-lg border border-slate-200 bg-slate-50/60 px-3 py-2">
                            <div class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">Ketepatan Masuk</div>
                            <div class="mt-0.5 font-semibold {{ ! $ci ? 'text-slate-400' : ($isLate ? 'text-red-700' : 'text-emerald-700') }}">{{ ! $ci ? '—' : ($isLate ? 'Terlambat' : 'Tepat Waktu') }}</div>
                        </div>
                        <div class="rounded-lg border border-slate-200 bg-slate-50/60 px-3 py-2">
                            <div class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">Durasi Kerja</div>
                            <div class="mt-0.5 font-mono font-semibold tabular-nums text-slate-900">{{ $r->work_duration ?? '—' }}</div>
                        </div>
                        <div class="rounded-lg border border-slate-200 bg-slate-50/60 px-3 py-2">
                            <div class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">Lembur</div>
                            <div class="mt-0.5 font-mono font-semibold tabular-nums text-slate-900">{{ $r->overtime_duration ?? '-' }}</div>
                        </div>
                        <div class="col-span-2 rounded-lg border border-slate-200 bg-slate-50/60 px-3 py-2">
                            <div class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">Fase Lembur</div>
                            <div class="mt-1 grid grid-cols-4 gap-2 font-mono text-slate-900">
                                @foreach([0, 1, 2, 3] as $phaseIndex)
                                    @php $phaseMinutes = $r->overtime_phases[$phaseIndex]['minutes'] ?? 0; @endphp
                                    <div>
                                        <div class="text-[10px] font-sans font-semibold text-slate-400">F{{ $phaseIndex + 1 }}</div>
                                        <div>{{ intdiv($phaseMinutes, 60) }}j {{ $phaseMinutes % 60 }}m</div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    <div x-show="photoOpen" x-cloak class="overflow-hidden rounded-xl border border-slate-200 bg-white">
                        @if($r->check_in_photo_url)
                             <img x-bind:src="photoOpen ? '{{ $r->check_in_photo_url }}' : null" loading="lazy" alt="Foto absensi {{ $user?->name ?? 'pegawai' }}" class="h-52 w-full object-cover">
                            <div class="border-t border-slate-100 px-3 py-2 text-xs text-slate-500">{{ $r->check_in_photo_taken_at?->setTimezone(config('app.timezone'))?->format('d M Y, H:i') ?? 'Waktu foto tidak tersedia' }}</div>
                        @else
                            <div class="px-3 py-6 text-center text-sm text-slate-400">Tidak ada foto tersimpan.</div>
                        @endif
                    </div>
                </div>
            @empty
                <div class="px-5 py-10 text-center text-sm text-slate-400">Tidak ada catatan absensi pada filter ini.</div>
            @endforelse
        </div>

        @if ($records->hasPages())
        <div class="flex flex-col gap-2 border-t border-slate-100 px-5 py-3 text-xs text-slate-500 sm:flex-row sm:items-center sm:justify-between">
            <div>Menampilkan {{ $records->firstItem() ?? 0 }}–{{ $records->lastItem() ?? 0 }} dari {{ $records->total() }}</div>
            <div>{{ $records->links('pagination::tailwind') }}</div>
        </div>
        @endif
    </section>
    @else
    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
        <div class="hidden overflow-x-auto md:block">
            <table class="w-full min-w-[980px] border-collapse text-sm">
                <thead>
                    <tr class="border-b border-slate-100 text-left text-[11px] font-semibold uppercase tracking-wide text-slate-400">
                        <th rowspan="2" class="px-5 py-3">No. Pegawai</th>
                        <th rowspan="2" class="px-5 py-3">Nama Pegawai</th>
                        <th rowspan="2" class="px-5 py-3 text-center">Hari</th>
                        <th rowspan="2" class="px-5 py-3">Durasi Kerja</th>
                        <th rowspan="2" class="px-5 py-3">Lembur</th>
                        @foreach([1, 2, 3, 4] as $phase)
                            <th colspan="2" class="px-3 py-2 text-center">Fase {{ $phase }}</th>
                        @endforeach
                    </tr>
                    <tr class="border-b border-slate-100 text-center text-[10px] font-semibold uppercase tracking-wide text-slate-400">
                        @foreach([1, 2, 3, 4] as $phase)
                            <th class="px-3 py-1.5">Jam</th><th class="px-3 py-1.5">Menit</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($summaryRows as $row)
                        <tr class="align-top">
                            <td class="px-5 py-3.5 font-mono text-xs text-slate-500">{{ $row['user']?->employee_number ?? '—' }}</td>
                            <td class="px-5 py-3.5 font-semibold text-slate-900">{{ $row['user']?->name ?? '—' }}</td>
                            <td class="px-5 py-3.5 text-center font-mono tabular-nums text-slate-700">{{ $row['days'] }}</td>
                            <td class="px-5 py-3.5 font-mono tabular-nums text-slate-700">{{ $row['work_duration'] }}</td>
                            <td class="px-5 py-3.5 font-mono tabular-nums text-slate-700">{{ $row['overtime_duration'] }}</td>
                            @foreach($row['phases'] as $phase)
                                <td class="px-3 py-3.5 text-center font-mono tabular-nums text-slate-700">{{ $phase['hours'] }}</td>
                                <td class="px-3 py-3.5 text-center font-mono tabular-nums text-slate-700">{{ $phase['minutes'] }}</td>
                            @endforeach
                        </tr>
                    @empty
                        <tr><td colspan="13" class="px-5 py-10 text-center text-sm text-slate-400">Tidak ada pegawai pada filter ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="divide-y divide-slate-100 md:hidden">
            @forelse($summaryRows as $row)
                <article class="space-y-3 px-4 py-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <div class="truncate text-sm font-semibold text-slate-900">{{ $row['user']?->name ?? '—' }}</div>
                            <div class="font-mono text-xs text-slate-500">{{ $row['user']?->employee_number ?? '—' }}</div>
                        </div>
                        <div class="rounded-lg bg-slate-50 px-2.5 py-1 text-right">
                            <div class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">Hari</div>
                            <div class="font-mono text-sm font-semibold tabular-nums text-slate-900">{{ $row['days'] }}</div>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-2 text-xs">
                        <div class="rounded-lg border border-slate-200 bg-slate-50/60 px-3 py-2"><div class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">Durasi kerja</div><div class="mt-0.5 font-mono font-semibold tabular-nums text-slate-900">{{ $row['work_duration'] }}</div></div>
                        <div class="rounded-lg border border-slate-200 bg-slate-50/60 px-3 py-2"><div class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">Lembur</div><div class="mt-0.5 font-mono font-semibold tabular-nums text-slate-900">{{ $row['overtime_duration'] }}</div></div>
                    </div>
                    <div class="grid grid-cols-2 gap-2 text-xs sm:grid-cols-4">
                        @foreach($row['phases'] as $index => $phase)
                            <div class="rounded-lg border border-slate-200 bg-slate-50/60 px-2.5 py-2"><div class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">Fase {{ $index + 1 }}</div><div class="mt-0.5 font-mono font-semibold tabular-nums text-slate-900">{{ $phase['hours'] }}j {{ $phase['minutes'] }}m</div></div>
                        @endforeach
                    </div>
                </article>
            @empty
                <div class="px-5 py-10 text-center text-sm text-slate-400">Tidak ada pegawai pada filter ini.</div>
            @endforelse
        </div>
     </section>
     @if ($summaryRows->hasPages())
     <div class="flex flex-col gap-2 border-t border-slate-100 px-5 py-3 text-xs text-slate-500 sm:flex-row sm:items-center sm:justify-between">
         <div>Menampilkan {{ $summaryRows->firstItem() ?? 0 }}–{{ $summaryRows->lastItem() ?? 0 }} dari {{ $summaryRows->total() }}</div>
         <div>{{ $summaryRows->links('pagination::tailwind') }}</div>
     </div>
     @endif
     @endif
</div>
@endsection
