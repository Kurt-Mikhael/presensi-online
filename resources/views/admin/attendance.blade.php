@extends('layouts.app', ['user' => auth()->user()])

@section('title', 'Daftar Presensi')

@section('content')
@php
    $total   = $stats['total'];
    $masuk   = $stats['masuk'];
    $pulang  = $stats['pulang'];
    $pending = $stats['pending'];
@endphp

<div class="space-y-5">
    <header class="flex flex-col gap-1">
        <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Daftar Presensi</h1>
        <p class="text-sm text-slate-500">Pantau catatan presensi seluruh karyawan berdasarkan periode.</p>
    </header>

    {{-- Filter --}}
    <section class="rounded-2xl border border-slate-200 bg-white p-4 sm:p-5">
        <form method="get" class="grid grid-cols-1 gap-3 sm:grid-cols-[1fr_1fr_1fr_auto] sm:items-end">
            <div>
                <label for="date_from" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Dari tanggal</label>
                <div class="relative mt-1.5">
                    <span class="pointer-events-none absolute inset-y-0 left-0 grid w-10 place-items-center text-slate-400">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5"/></svg>
                    </span>
                    <input id="date_from" type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="block w-full rounded-lg border border-slate-300 bg-white py-2.5 pl-10 pr-3 text-sm text-slate-900 focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-600/20">
                </div>
            </div>
            <div>
                <label for="date_to" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Sampai tanggal</label>
                <div class="relative mt-1.5">
                    <span class="pointer-events-none absolute inset-y-0 left-0 grid w-10 place-items-center text-slate-400">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5"/></svg>
                    </span>
                    <input id="date_to" type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="block w-full rounded-lg border border-slate-300 bg-white py-2.5 pl-10 pr-3 text-sm text-slate-900 focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-600/20">
                </div>
            </div>
            <div>
                <label for="q" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Cari nama atau nomor pegawai</label>
                <div class="relative mt-1.5">
                    <span class="pointer-events-none absolute inset-y-0 left-0 grid w-10 place-items-center text-slate-400">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
                    </span>
                    <input id="q" type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Cari nama atau nomor pegawai…" class="block w-full rounded-lg border border-slate-300 bg-white py-2.5 pl-10 pr-3 text-sm text-slate-900 placeholder:text-slate-400 focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-600/20">
                </div>
            </div>
            <div class="flex flex-wrap gap-2">
                <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-brand-700 sm:w-auto">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
                    Cari
                </button>
                <button type="submit" formaction="{{ route('admin.attendance.export') }}" formtarget="_blank" class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700 sm:w-auto">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v12m0 0 4-4m-4 4-4-4M5 21h14"/></svg>
                    Export Excel
                </button>
                <a href="{{ route('admin.attendance.index') }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Reset</a>
            </div>
        </form>
    </section>

    {{-- Stat cards --}}
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

    {{-- Tabel (desktop) --}}
    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
        <div class="hidden overflow-x-auto md:block">
            <table class="w-full min-w-[980px] border-collapse text-sm">
                <thead>
                    <tr class="border-b border-slate-100 text-left text-[11px] font-semibold uppercase tracking-wide text-slate-400">
                        <th class="px-5 py-3">No. Pegawai</th>
                        <th class="px-5 py-3">Nama Pegawai</th>
                        <th class="px-5 py-3">Jam Masuk</th>
                        <th class="px-5 py-3">Jam Pulang</th>
                        <th class="px-5 py-3">Durasi Kerja</th>
                        <th class="px-5 py-3">Lembur</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                @forelse($records as $r)
                        @php
                            $user = $r->user;
                            $ci = $r->check_in_at?->setTimezone(config('app.timezone'));
                            $co = $r->check_out_at?->setTimezone(config('app.timezone'));
                        @endphp
                <tbody x-data="{ photoOpen: false }" class="divide-y divide-slate-100">
                        <tr class="align-top">
                            <td class="px-5 py-3.5 font-mono text-xs text-slate-500">{{ $user?->employee_number ?? '—' }}</td>
                            <td class="px-5 py-3.5 font-semibold text-slate-900">{{ $user?->name ?? '—' }}</td>
                            <td class="px-5 py-3.5 font-mono tabular-nums text-slate-700">{{ $ci?->format('H:i') ?? '—' }}</td>
                            <td class="px-5 py-3.5 font-mono tabular-nums text-slate-700">{{ $co?->format('H:i') ?? '—' }}</td>
                            <td class="px-5 py-3.5 font-mono tabular-nums text-slate-700">{{ $r->work_duration ?? '—' }}</td>
                            <td class="px-5 py-3.5 font-mono tabular-nums text-slate-700">{{ $r->overtime_duration ?? '-' }}</td>
                            <td class="px-5 py-3.5">
                                @if($r->check_in_at && $r->check_out_at)
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
                                        Belum Presensi
                                    </span>
                                @endif
                            </td>
                            <td class="px-5 py-3.5 text-right">
                                @if($r->check_in_photo_url)
                                    <button type="button" @click="photoOpen = !photoOpen" class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-600 transition hover:border-brand-200 hover:bg-brand-50 hover:text-brand-700" :class="photoOpen ? 'border-brand-200 bg-brand-50 text-brand-700' : ''" aria-label="Lihat foto">
                                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
                                        <span x-text="photoOpen ? 'Tutup Foto' : 'Lihat Foto'"></span>
                                    </button>
                                @else
                                    <span class="text-xs text-slate-400">Belum ada foto</span>
                                @endif
                            </td>
                        </tr>
                        <tr x-show="photoOpen" x-cloak>
                            <td colspan="8" class="bg-slate-50 px-5 py-4">
                                <div class="grid gap-4 lg:grid-cols-[220px_1fr]">
                                    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
                                        @if($r->check_in_photo_url)
                                            <img src="{{ $r->check_in_photo_url }}" alt="Foto presensi {{ $user?->name ?? 'pegawai' }}" class="h-56 w-full object-cover">
                                        @else
                                            <div class="grid h-56 place-items-center text-sm text-slate-400">Tidak ada foto tersimpan.</div>
                                        @endif
                                    </div>
                                    <div class="flex items-center">
                                        <div>
                                            <div class="text-sm font-semibold text-slate-900">Foto presensi {{ $user?->name ?? 'pegawai' }}</div>
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
                    <tr><td colspan="8" class="px-5 py-10 text-center text-sm text-slate-400">Tidak ada catatan presensi pada filter ini.</td></tr>
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
                @endphp
                <div x-data="{ photoOpen: false }" class="space-y-2.5 px-4 py-3.5">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <div class="truncate text-sm font-semibold text-slate-900">{{ $user?->name ?? '—' }}</div>
                            <div class="font-mono text-xs text-slate-500">{{ $user?->employee_number ?? '—' }}</div>
                        </div>
                        <div class="flex-none">
                            @if($r->check_in_at && $r->check_out_at)
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
                                    Belum Presensi
                                </span>
                            @endif
                        </div>
                    </div>
                    <div class="flex items-center justify-between gap-2">
                        <div class="text-xs text-slate-400">Foto presensi masuk</div>
                        @if($r->check_in_photo_url)
                            <button type="button" @click="photoOpen = !photoOpen" class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-600 transition hover:border-brand-200 hover:bg-brand-50 hover:text-brand-700" :class="photoOpen ? 'border-brand-200 bg-brand-50 text-brand-700' : ''">
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
                                <span x-text="photoOpen ? 'Tutup Foto' : 'Lihat Foto'"></span>
                            </button>
                        @else
                            <span class="text-xs text-slate-400">Belum ada foto</span>
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
                            <div class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">Durasi Kerja</div>
                            <div class="mt-0.5 font-mono font-semibold tabular-nums text-slate-900">{{ $r->work_duration ?? '—' }}</div>
                        </div>
                        <div class="rounded-lg border border-slate-200 bg-slate-50/60 px-3 py-2">
                            <div class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">Lembur</div>
                            <div class="mt-0.5 font-mono font-semibold tabular-nums text-slate-900">{{ $r->overtime_duration ?? '-' }}</div>
                        </div>
                    </div>
                    <div x-show="photoOpen" x-cloak class="overflow-hidden rounded-xl border border-slate-200 bg-white">
                        @if($r->check_in_photo_url)
                            <img src="{{ $r->check_in_photo_url }}" alt="Foto presensi {{ $user?->name ?? 'pegawai' }}" class="h-52 w-full object-cover">
                            <div class="border-t border-slate-100 px-3 py-2 text-xs text-slate-500">{{ $r->check_in_photo_taken_at?->setTimezone(config('app.timezone'))?->format('d M Y, H:i') ?? 'Waktu foto tidak tersedia' }}</div>
                        @else
                            <div class="px-3 py-6 text-center text-sm text-slate-400">Tidak ada foto tersimpan.</div>
                        @endif
                    </div>
                </div>
            @empty
                <div class="px-5 py-10 text-center text-sm text-slate-400">Tidak ada catatan presensi pada filter ini.</div>
            @endforelse
        </div>

        @if ($records->hasPages())
        <div class="flex flex-col gap-2 border-t border-slate-100 px-5 py-3 text-xs text-slate-500 sm:flex-row sm:items-center sm:justify-between">
            <div>Menampilkan {{ $records->firstItem() ?? 0 }}–{{ $records->lastItem() ?? 0 }} dari {{ $records->total() }}</div>
            <div>{{ $records->links('pagination::tailwind') }}</div>
        </div>
        @endif
    </section>
</div>
@endsection
