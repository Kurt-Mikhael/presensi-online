@extends('layouts.app', ['user' => $user])

@section('title', 'Dashboard Absensi')

@section('content')
<div
    x-data="presensiPage({{ Js::from([
        'check_in_at' => $record->check_in_at?->toIso8601String(),
        'check_out_at' => $record->check_out_at?->toIso8601String(),
        'check_in_accuracy' => $record->check_in_accuracy,
        'check_out_accuracy' => $record->check_out_accuracy,
        'check_in_photo_url' => $record->check_in_photo_url,
        'check_in_photo_taken_at' => $record->check_in_photo_taken_at?->toIso8601String(),
        'areas' => $areas,
    ]) }})"
    class="space-y-5"
>
    {{-- Header --}}
    <header class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Dashboard</h1>
            <p class="mt-1 text-sm text-slate-500">Catat kehadiran Anda hari ini dengan akurat.</p>
        </div>
        <div class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-500">
            <span x-show="!connection.checked" class="text-slate-400">Memeriksa…</span>
            <span x-show="connection.checked && connection.online" class="inline-flex items-center gap-1.5 text-blue-700"><span class="h-1.5 w-1.5 rounded-full bg-blue-500"></span>Sistem online</span>
            <span x-show="connection.checked && connection.online === false" class="inline-flex items-center gap-1.5 text-red-700"><span class="h-1.5 w-1.5 rounded-full bg-red-500"></span>Sistem offline</span>
        </div>
    </header>

    {{-- Row 1: Date/clock + area status + map --}}
    <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
        {{-- Date + jam besar + GPS metrics --}}
        <section class="rounded-2xl border border-slate-200 bg-white p-5 lg:col-span-2">
            <div class="flex flex-col gap-1.5">
                <div class="text-xs font-semibold uppercase tracking-wide text-slate-400" x-text="todayLabel">Senin, 28 Juli 2026</div>
                <div class="font-mono text-4xl font-semibold tabular-nums tracking-tight text-slate-900 sm:text-5xl" x-text="serverClock">--:--:--</div>
            </div>

            <div class="mt-5 grid grid-cols-1 gap-3 border-t border-slate-100 pt-4">
                <div class="rounded-xl border border-slate-200 bg-slate-50/60 px-3.5 py-3">
                    <div class="flex items-center gap-2 text-xs text-slate-500">
                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" d="M12 7v5l3 2"/></svg>
                        Status Area
                    </div>
                    <div class="mt-1 text-sm font-semibold"
                         :class="loc.phase === 'inside' ? 'text-blue-700' : (loc.phase === 'outside' || loc.phase === 'error' ? 'text-red-600' : 'text-slate-400')">
                        <template x-if="loc.phase === 'inside'"><span x-text="'Dalam Area · ' + loc.areaName"></span></template>
                        <template x-if="loc.phase === 'outside'"><span>Di Luar Area Absensi</span></template>
                        <template x-if="loc.phase === 'no_area'"><span>Belum Ada Area Aktif</span></template>
                        <template x-if="loc.phase === 'searching'"><span>Menentukan…</span></template>
                        <template x-if="loc.phase === 'error'"><span x-text="loc.error"></span></template>
                    </div>
                </div>
            </div>
        </section>

        {{-- Mini map / area info --}}
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
            <div class="relative h-44 w-full sm:h-52" x-show="areas.length > 0">
                <div id="mini-map" class="h-full w-full"></div>
            </div>
            <div class="flex h-32 items-center justify-center px-4 text-center text-xs text-slate-400" x-show="areas.length === 0" x-cloak>
                Belum ada area absensi aktif
             </div>
             <div class="border-t border-slate-100 px-4 py-3 text-xs">
                 <div class="font-semibold text-slate-700">Lokasi Anda</div>
                 <template x-if="loc.phase === 'inside'">
                     <div class="mt-1 text-slate-500">
                        Jarak dari titik pusat: <span class="font-mono tabular-nums" x-text="Math.round(loc.distance)"></span> m
                        <template x-if="loc.radius">
                            <span> · Radius absensi: <span class="font-mono tabular-nums" x-text="loc.radius"></span> m</span>
                        </template>
                    </div>
                </template>
            </div>
        </section>
    </div>

    <section class="rounded-2xl border border-slate-200 bg-white p-5">
        <h2 class="text-base font-semibold text-slate-900">Absensi Hari Ini</h2>
        <p class="mt-1 text-sm text-slate-500">Masuk dan pulang sesuai jadwal kerja.</p>

        <div class="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-3">
            <div class="flex flex-col gap-4 lg:col-span-2">
                {{-- Masuk --}}
                <section class="rounded-2xl border border-slate-200 bg-white p-5">
            <div class="flex items-start justify-between gap-3">
                <span class="grid h-11 w-11 place-items-center rounded-xl bg-blue-600 text-white shadow-sm">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15M9 12l3-3m0 0 3 3m-3-3v12"/></svg>
                </span>
                <span class="inline-flex items-center gap-1.5 rounded-md px-2 py-0.5 text-xs font-semibold"
                      :class="record.has_check_in ? 'bg-blue-50 text-blue-700' : 'bg-slate-100 text-slate-500'">
                    <span class="h-1.5 w-1.5 rounded-full" :class="record.has_check_in ? 'bg-blue-500' : 'bg-slate-400'"></span>
                    <span x-text="record.has_check_in ? 'BERHASIL' : 'BELUM ABSENSI'">BELUM ABSENSI</span>
                </span>
            </div>
            <h2 class="mt-4 text-base font-semibold text-slate-900">Check In</h2>

            <div class="mt-2 flex items-center gap-2 text-sm">
                <svg class="h-4 w-4 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" d="M12 7v5l3 2"/></svg>
                <span class="text-slate-500">Batas Absensi Masuk</span>
                <span class="font-mono font-semibold tabular-nums text-slate-900">{{ config('attendance.check_in_deadline', '08:30') }} WIB</span>
            </div>

             {{-- Belum absensi: tombol --}}
             <template x-if="!record.has_check_in">
                 <div>
                     <button type="button" @click="doCheckIn()" :disabled="!canCheckIn"
                             :class="!canCheckIn
                                ? 'bg-slate-100 text-slate-400 cursor-not-allowed'
                                : 'bg-brand-600 text-white hover:bg-brand-700 active:bg-brand-800'"
                            class="mt-4 inline-flex w-full items-center justify-center gap-2 rounded-xl px-4 py-3.5 text-sm font-semibold tracking-tight transition focus:outline-none focus-visible:ring-4 focus-visible:ring-brand-600/30">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                        <span x-text="checkInButtonLabel">Check In</span>
                    </button>
                </div>
            </template>

            {{-- Sudah absensi: ringkasan sukses, tanpa tombol --}}
            <template x-if="record.has_check_in">
                <div class="mt-4 rounded-xl border border-blue-200 bg-blue-50/60 px-4 py-3.5">
                    <div class="flex items-center gap-2 text-sm font-semibold text-blue-800">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                        Check In berhasil
                    </div>
                    <div class="mt-2 font-mono text-2xl font-semibold tabular-nums text-slate-900">
                        <span x-text="fmtClock(record.check_in_at)"></span> <span class="text-sm font-normal text-slate-500">WIB</span>
                    </div>
                    <div class="mt-1 space-y-0.5 text-xs text-slate-500">
                        <template x-if="record.check_in_area">
                            <div x-text="record.check_in_area"></div>
                        </template>
                        <template x-if="record.check_in_accuracy !== null">
                            <div>Akurasi lokasi ±<span class="font-mono" x-text="Math.round(record.check_in_accuracy)"></span> m</div>
                        </template>
                    </div>
                    <div class="mt-2.5 text-xs italic text-blue-700">Semangat untuk kerja hari ini ya!</div>
                </div>
            </template>
                </section>

                {{-- Pulang --}}
                <section class="rounded-2xl border border-slate-200 bg-white p-5 transition"
                 :class="!record.has_check_in ? 'opacity-60' : ''">
            <div class="flex items-start justify-between gap-3">
                <span class="grid h-11 w-11 place-items-center rounded-xl bg-blue-600 text-white shadow-sm">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15M9 12l3-3m0 0 3 3m-3-3V3"/></svg>
                </span>
                <span class="inline-flex items-center gap-1.5 rounded-md px-2 py-0.5 text-xs font-semibold"
                      :class="record.has_check_out ? 'bg-blue-50 text-blue-700' : (record.has_check_in ? 'bg-brand-50 text-brand-700' : 'bg-slate-100 text-slate-500')">
                    <span class="h-1.5 w-1.5 rounded-full"
                          :class="record.has_check_out ? 'bg-blue-500' : (record.has_check_in ? 'bg-brand-600' : 'bg-slate-400')"></span>
                    <span x-text="record.has_check_out ? 'SELESAI' : (record.has_check_in ? 'TERSEDIA' : 'TERKUNCI')">TERKUNCI</span>
                </span>
            </div>
            <h2 class="mt-4 text-base font-semibold text-slate-900">Check Out</h2>

            <div class="mt-2 flex items-center gap-2 text-sm">
                <svg class="h-4 w-4 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" d="M12 7v5l3 2"/></svg>
                <span class="text-slate-500">Jadwal Pulang</span>
                <span class="font-mono font-semibold tabular-nums text-slate-900">{{ config('attendance.work_end', '17:00') }} WIB</span>
            </div>

            {{-- Terkunci: belum absensi masuk --}}
            <template x-if="!record.has_check_in">
                <div>
                    <p class="mt-3 text-sm text-slate-500">Absensi pulang tersedia setelah Anda melakukan absensi masuk.</p>
                    <button type="button" disabled
                            class="mt-4 inline-flex w-full cursor-not-allowed items-center justify-center gap-2 rounded-xl bg-slate-100 px-4 py-3.5 text-sm font-semibold tracking-tight text-slate-400">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"/></svg>
                        Absensi Masuk Terlebih Dahulu
                    </button>
                </div>
            </template>

             {{-- Tersedia: sudah masuk, belum pulang --}}
             <template x-if="record.has_check_in && !record.has_check_out">
                 <div>
                     <button type="button" @click="doCheckOut()" :disabled="!canCheckOut"
                             :class="!canCheckOut
                                ? 'bg-slate-100 text-slate-400 cursor-not-allowed'
                                : 'bg-brand-600 text-white hover:bg-brand-700 active:bg-brand-800'"
                            class="mt-4 inline-flex w-full items-center justify-center gap-2 rounded-xl px-4 py-3.5 text-sm font-semibold tracking-tight transition focus:outline-none focus-visible:ring-4 focus-visible:ring-brand-600/30">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                        <span x-text="loc.phase === 'inside' ? 'Check Out' : (loc.phase === 'outside' ? 'Di Luar Area Absensi' : 'Check Out')">Check Out</span>
                    </button>
                </div>
            </template>

            {{-- Selesai: sudah pulang --}}
            <template x-if="record.has_check_out">
                <div class="mt-4 rounded-xl border border-blue-200 bg-blue-50/60 px-4 py-3.5">
                    <div class="flex items-center gap-2 text-sm font-semibold text-blue-800">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                        Check Out berhasil
                    </div>
                    <div class="mt-2 font-mono text-2xl font-semibold tabular-nums text-slate-900">
                        <span x-text="fmtClock(record.check_out_at)"></span> <span class="text-sm font-normal text-slate-500">WIB</span>
                    </div>
                    <div class="mt-1 space-y-0.5 text-xs text-slate-500">
                        <template x-if="record.check_out_area">
                            <div x-text="record.check_out_area"></div>
                        </template>
                        <template x-if="record.check_out_accuracy !== null">
                            <div>Akurasi lokasi ±<span class="font-mono" x-text="Math.round(record.check_out_accuracy)"></span> m</div>
                        </template>
                    </div>
                    <div class="mt-2.5 text-xs italic text-blue-700">Terima kasih sudah bekerja hari ini, sampai jumpa besok!</div>
                </div>
            </template>
                </section>
            </div>

                {{-- Foto Absensi --}}
                <section class="flex flex-col rounded-2xl border border-slate-200 bg-white p-5">
            <div class="flex items-start justify-between gap-3">
                <span class="grid h-11 w-11 place-items-center rounded-xl bg-blue-600 text-white shadow-sm">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 7.5h3l1.5-2.25h7.5L17.25 7.5h3A2.25 2.25 0 0 1 22.5 9.75v7.5A2.25 2.25 0 0 1 20.25 19.5H3.75A2.25 2.25 0 0 1 1.5 17.25v-7.5A2.25 2.25 0 0 1 3.75 7.5Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"/></svg>
                </span>
                <span class="inline-flex items-center gap-1.5 rounded-md bg-brand-50 px-2 py-0.5 text-xs font-semibold text-brand-700">
                    Auto capture
                </span>
            </div>
            <h2 class="mt-4 text-base font-semibold text-slate-900">Foto Absensi</h2>

            <div class="mt-2 flex items-center gap-2 text-sm">
            </div>

            <div class="relative mx-auto my-auto aspect-square w-40 overflow-hidden rounded-full border border-slate-200 bg-slate-50 shadow-sm">
                <template x-if="photo.previewUrl">
                            <img :src="photo.previewUrl" alt="Foto absensi hari ini" class="h-full w-full object-cover">
                </template>
                <template x-if="!photo.previewUrl">
                    <div class="flex h-full items-center justify-center text-slate-300">
                        <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 7.5h3l1.5-2.25h7.5L17.25 7.5h3A2.25 2.25 0 0 1 22.5 9.75v7.5A2.25 2.25 0 0 1 20.25 19.5H3.75A2.25 2.25 0 0 1 1.5 17.25v-7.5A2.25 2.25 0 0 1 3.75 7.5Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"/></svg>
                    </div>
                </template>

                <div x-show="photo.capturing" x-cloak class="absolute inset-0 grid place-items-center bg-slate-950/45 text-white">
                    <div class="rounded-full border border-white/15 bg-slate-950/30 p-2.5 backdrop-blur">
                        <svg class="h-4 w-4 animate-spin text-white/80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M12 4.5V2m0 20v-2.5M4.5 12H2m20 0h-2.5M5.6 5.6 7 7m10 10 1.4 1.4M5.6 18.4 7 17m10-10 1.4-1.4"/></svg>
                    </div>
                </div>
            </div>

            <div class="mt-4 flex flex-wrap items-center justify-center gap-2 text-xs text-slate-500">
                <span class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 font-medium text-slate-600">
                    Diambil pukul 
                </span>
                <span x-show="photo.takenAt" class="font-mono tabular-nums text-slate-500" x-text="photo.takenAt ? fmtClock(photo.takenAt) + ' WIB' : ''"></span>
            </div>

            <template x-if="photo.error">
                <div class="mt-3 rounded-xl border border-red-200 bg-red-50 px-3.5 py-3 text-sm text-red-700" x-text="photo.error"></div>
            </template>
                </section>
            </div>
        </section>

    {{-- Status panel (live feedback) --}}
    <section
        x-show="busy || status.phase !== 'idle'"
        x-transition.opacity
        class="rounded-2xl border bg-white p-4"
        :class="{
            'border-amber-200 bg-amber-50/40': busy && status.phase !== 'done',
            'border-blue-200 bg-blue-50/40': status.phase === 'done',
            'border-red-200 bg-red-50/40': status.phase === 'error',
        }"
    >
        <div class="flex items-center gap-3">
            <span class="grid h-9 w-9 flex-none place-items-center rounded-lg"
                  :class="{
                      'bg-amber-100 text-amber-700': busy && status.phase !== 'done',
                      'bg-blue-100 text-blue-700': status.phase === 'done',
                      'bg-red-100 text-red-700': status.phase === 'error',
                  }">
                <template x-if="busy || (status.phase !== 'done' && status.phase !== 'error')">
                    <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M12 4.5V2m0 20v-2.5M4.5 12H2m20 0h-2.5M5.6 5.6 7 7m10 10 1.4 1.4M5.6 18.4 7 17m10-10 1.4-1.4"/></svg>
                </template>
                <template x-if="status.phase === 'done'">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                </template>
                <template x-if="status.phase === 'error'">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 3.75h.008v.008H12v-.008Zm9 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                </template>
            </span>
            <div class="min-w-0 flex-1">
                <div class="text-sm font-medium"
                     :class="{
                         'text-amber-800': busy && status.phase !== 'done',
                         'text-blue-800': status.phase === 'done',
                         'text-red-800': status.phase === 'error',
                     }"
                     x-text="status.message || 'Siap'"></div>
                <div x-show="status.accuracy !== null" class="mt-0.5 text-xs text-slate-500">
                    Akurasi GPS: <span class="font-mono tabular-nums" x-text="Math.round(status.accuracy)"></span> meter
                </div>
            </div>
            <div class="text-xs text-slate-500">
                <span x-show="!connection.checked">Memeriksa…</span>
                <span x-show="connection.checked && connection.online" class="text-blue-600">● Online</span>
                <span x-show="connection.checked && connection.online === false" class="text-red-600">● Offline</span>
            </div>
        </div>
    </section>

    {{-- Riwayat Terakhir --}}
    <section class="rounded-2xl border border-slate-200 bg-white">
        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
            <h2 class="text-base font-semibold text-slate-900">Riwayat Terakhir</h2>
            <a href="{{ route('attendance.history') }}" class="text-xs font-medium text-brand-700 transition hover:text-brand-800 hover:underline">Lihat Semua →</a>
        </div>

        <div class="hidden overflow-x-auto md:block">
            <table class="w-full min-w-[640px] border-collapse text-sm">
                <thead>
                    <tr class="border-b border-slate-100 text-left text-[11px] font-semibold uppercase tracking-wide text-slate-400">
                        <th class="px-5 py-3">Tanggal</th>
                        <th class="px-5 py-3">Masuk</th>
                        <th class="px-5 py-3">Pulang</th>
                        <th class="px-5 py-3">Status</th>

                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($recent as $r)
                        <tr>
                            <td class="px-5 py-3.5 text-slate-700">{{ $r->attendance_date?->translatedFormat('l, j M Y') }}</td>
                            <td class="px-5 py-3.5 font-mono tabular-nums text-slate-900">{{ $r->check_in_at?->setTimezone(config('app.timezone'))->format('H:i') }}</td>
                            <td class="px-5 py-3.5 font-mono tabular-nums text-slate-900">{{ $r->check_out_at?->setTimezone(config('app.timezone'))->format('H:i') ?? '—' }}</td>
                            <td class="px-5 py-3.5">
                                @if($r->check_in_at && $r->check_out_at)
                                    <span class="inline-flex items-center gap-1.5 rounded-md bg-blue-50 px-2 py-0.5 text-xs font-semibold text-blue-700">Tepat Waktu</span>
                                @elseif($r->check_in_at)
                                    <span class="inline-flex items-center gap-1.5 rounded-md bg-amber-50 px-2 py-0.5 text-xs font-semibold text-amber-700">Belum Pulang</span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 rounded-md bg-red-50 px-2 py-0.5 text-xs font-semibold text-red-700">Tidak Hadir</span>
                                @endif
                            </td>

                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-5 py-8 text-center text-sm text-slate-400">Belum ada riwayat absensi.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Kartu riwayat (mobile) --}}
        <div class="divide-y divide-slate-100 md:hidden">
            @forelse($recent as $r)
                <div class="space-y-2 px-4 py-3.5">
                    <div class="flex items-center justify-between gap-2">
                        <div class="text-sm font-semibold text-slate-800">{{ $r->attendance_date?->translatedFormat('l, j M Y') }}</div>
                        @if($r->check_in_at && $r->check_out_at)
                            <span class="inline-flex flex-none items-center gap-1.5 rounded-md bg-blue-50 px-2 py-0.5 text-xs font-semibold text-blue-700">Tepat Waktu</span>
                        @elseif($r->check_in_at)
                            <span class="inline-flex flex-none items-center gap-1.5 rounded-md bg-amber-50 px-2 py-0.5 text-xs font-semibold text-amber-700">Belum Pulang</span>
                        @else
                            <span class="inline-flex flex-none items-center gap-1.5 rounded-md bg-red-50 px-2 py-0.5 text-xs font-semibold text-red-700">Tidak Hadir</span>
                        @endif
                    </div>
                    <div class="grid grid-cols-2 gap-2 text-xs">
                        <div class="rounded-lg border border-slate-200 bg-slate-50/60 px-3 py-2">
                            <div class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">Masuk</div>
                            <div class="mt-0.5 font-mono font-semibold tabular-nums text-slate-900">{{ $r->check_in_at?->setTimezone(config('app.timezone'))->format('H:i') ?? '—' }}</div>
                        </div>
                        <div class="rounded-lg border border-slate-200 bg-slate-50/60 px-3 py-2">
                            <div class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">Pulang</div>
                            <div class="mt-0.5 font-mono font-semibold tabular-nums text-slate-900">{{ $r->check_out_at?->setTimezone(config('app.timezone'))->format('H:i') ?? '—' }}</div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="px-5 py-8 text-center text-sm text-slate-400">Belum ada riwayat absensi.</div>
            @endforelse
        </div>
    </section>
</div>

@vite(['resources/js/attendance.js'])
@push('scripts')
<script>
    window.fmtClock = function (iso) {
        if (!iso) return '--:--';
        const d = new Date(iso);
        const p = (n) => String(n).padStart(2, '0');
        return `${p(d.getHours())}:${p(d.getMinutes())}`;
    };
</script>
@endpush
@endsection
