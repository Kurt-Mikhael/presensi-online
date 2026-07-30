@extends('layouts.app', ['user' => $user])

@section('title', 'Riwayat Presensi')

@section('content')
<div class="space-y-5">
    {{-- Header --}}
    <header class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Riwayat Presensi</h1>
            <p class="mt-1 text-sm text-slate-500">Seluruh catatan kehadiran Anda, {{ $records->total() }} hari tercatat.</p>
        </div>
        <a href="{{ route('attendance.index') }}" class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-500 transition hover:border-brand-200 hover:text-brand-700">
            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            Kembali ke Dashboard
        </a>
    </header>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
        {{-- Tabel (desktop) --}}
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
                    @forelse($records as $r)
                        <tr>
                            <td class="px-5 py-3.5 text-slate-700">{{ $r->attendance_date?->translatedFormat('l, j M Y') }}</td>
                            <td class="px-5 py-3.5 font-mono tabular-nums text-slate-900">{{ $r->check_in_at?->setTimezone(config('app.timezone'))->format('H:i') ?? '—' }}</td>
                            <td class="px-5 py-3.5 font-mono tabular-nums text-slate-900">{{ $r->check_out_at?->setTimezone(config('app.timezone'))->format('H:i') ?? '—' }}</td>
                            <td class="px-5 py-3.5">
                                @if($r->check_in_at && $r->check_out_at)
                                    <span class="inline-flex items-center gap-1.5 rounded-md bg-emerald-50 px-2 py-0.5 text-xs font-semibold text-emerald-700">Tepat Waktu</span>
                                @elseif($r->check_in_at)
                                    <span class="inline-flex items-center gap-1.5 rounded-md bg-amber-50 px-2 py-0.5 text-xs font-semibold text-amber-700">Belum Pulang</span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 rounded-md bg-red-50 px-2 py-0.5 text-xs font-semibold text-red-700">Tidak Valid</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-5 py-8 text-center text-sm text-slate-400">Belum ada riwayat presensi.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Kartu riwayat (mobile) --}}
        <div class="divide-y divide-slate-100 md:hidden">
            @forelse($records as $r)
                <div class="space-y-2 px-4 py-3.5">
                    <div class="flex items-center justify-between gap-2">
                        <div class="text-sm font-semibold text-slate-800">{{ $r->attendance_date?->translatedFormat('l, j M Y') }}</div>
                        @if($r->check_in_at && $r->check_out_at)
                            <span class="inline-flex flex-none items-center gap-1.5 rounded-md bg-emerald-50 px-2 py-0.5 text-xs font-semibold text-emerald-700">Tepat Waktu</span>
                        @elseif($r->check_in_at)
                            <span class="inline-flex flex-none items-center gap-1.5 rounded-md bg-amber-50 px-2 py-0.5 text-xs font-semibold text-amber-700">Belum Pulang</span>
                        @else
                            <span class="inline-flex flex-none items-center gap-1.5 rounded-md bg-red-50 px-2 py-0.5 text-xs font-semibold text-red-700">Tidak Valid</span>
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
                <div class="px-5 py-8 text-center text-sm text-slate-400">Belum ada riwayat presensi.</div>
            @endforelse
        </div>

        {{-- Pagination --}}
        @if($records->hasPages())
            <div class="border-t border-slate-100 px-5 py-4">
                {{ $records->links() }}
            </div>
        @endif
    </section>
</div>
@endsection
