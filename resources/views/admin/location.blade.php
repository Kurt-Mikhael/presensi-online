@extends('layouts.app', ['user' => auth()->user()])

@section('title', 'Pengaturan Area Presensi')

@section('content')
<div
    x-data="adminLocationPage({{ Js::from([
        'areas' => $areas->map(fn($a) => app(\App\Http\Controllers\AdminLocationController::class)->serialize($a)),
        'activeId' => optional($active)->id,
        'mapCenter' => $mapCenter,
        'defaultAccuracy' => $defaultAccuracy,
    ]) }})"
    class="space-y-5"
>
    <header class="flex flex-col gap-1">
        <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Pengaturan Area Presensi</h1>
        <p class="text-sm text-slate-500">Tentukan titik dan radius area kerja untuk presensi karyawan.</p>
    </header>

    {{-- Pesan --}}
    <template x-if="message.text">
        <div x-transition.opacity
             class="rounded-xl border px-4 py-3 text-sm"
             :class="message.type === 'success' ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-red-200 bg-red-50 text-red-800'">
            <span x-text="message.text"></span>
        </div>
    </template>

    <div class="grid grid-cols-1 gap-5 lg:grid-cols-[1fr_minmax(0,360px)]">

        {{-- Peta --}}
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
            <div class="flex items-center justify-between border-b border-slate-200 px-4 py-3">
                <div class="text-sm font-semibold text-slate-700">Peta Area</div>
                <div class="flex items-center gap-3 text-[11px] text-slate-500">
                    <span class="flex items-center gap-1.5"><span class="inline-block h-0 w-4 border-t-[3px] border-brand-700"></span> Sedang Dipilih</span>
                    <span class="flex items-center gap-1.5"><span class="inline-block h-0 w-4 border-t border-brand-700/60"></span> Tersimpan</span>
                </div>
            </div>
            <div id="map" class="h-[340px] w-full sm:h-[560px]"></div>
            <div class="border-t border-slate-100 px-4 py-2.5 text-xs text-slate-500">
                Klik area pada peta untuk mengedit. Gunakan tombol toolbar (pojok kiri atas) untuk menggambar area baru.
            </div>
        </section>

        {{-- Panel form --}}
        <section class="self-start overflow-hidden rounded-2xl border border-slate-200 bg-white">

            <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                <h2 class="flex items-center gap-2 text-sm font-semibold text-slate-900">
                    <svg class="h-4 w-4 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z"/></svg>
                    Detail Lokasi
                </h2>
            </div>

            <div class="space-y-4 px-5 py-5">
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Nama Lokasi</label>
                    <input type="text" x-model="form.name" placeholder="Kantor Pusat" class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-600/20">
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Mode Geofence</label>
                    <div class="mt-1.5 grid grid-cols-2 overflow-hidden rounded-lg border border-slate-200 bg-slate-100 p-1 text-sm">
                        <button type="button" @click="selectType('circle')"
                                :class="form.area_type === 'circle' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-700'"
                                class="rounded-md px-3 py-2 font-semibold transition">Lingkaran</button>
                        <button type="button" @click="selectType('polygon')"
                                :class="form.area_type === 'polygon' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-700'"
                                class="rounded-md px-3 py-2 font-semibold transition">Polygon</button>
                    </div>
                </div>

                {{-- Lingkaran fields --}}
                <template x-if="form.area_type === 'circle'">
                    <div class="space-y-3">
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Latitude</label>
                                <input type="number" step="0.000001" x-model.number="form.center_lat" @input="updateFromInputs" class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 font-mono text-sm text-slate-900 focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-600/20">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Longitude</label>
                                <input type="number" step="0.000001" x-model.number="form.center_lng" @input="updateFromInputs" class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 font-mono text-sm text-slate-900 focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-600/20">
                            </div>
                        </div>
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Radius (meter)</label>
                                <div class="relative mt-1.5">
                                    <input type="number" min="1" max="50000" step="1" x-model.number="form.radius_meter" @input="updateFromInputs" class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 pr-10 font-mono text-sm text-slate-900 focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-600/20">
                                    <span class="pointer-events-none absolute inset-y-0 right-3 grid place-items-center text-xs text-slate-400">m</span>
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Max Akurasi GPS</label>
                                <div class="relative mt-1.5">
                                    <input type="number" min="0" max="500" step="1" x-model.number="form.maximum_accuracy_meter" class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 pr-10 font-mono text-sm text-slate-900 focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-600/20">
                                    <span class="pointer-events-none absolute inset-y-0 right-3 grid place-items-center text-xs text-slate-400">m</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>

                <template x-if="form.area_type === 'polygon'">
                    <div class="rounded-lg border border-slate-200 bg-slate-50 px-3.5 py-3 text-xs text-slate-600">
                        <div class="font-semibold text-slate-700">Polygon</div>
                        <div class="mt-0.5">Gambar polygon pada peta. <span x-text="form.polygon.length"></span> titik.</div>
                    </div>
                </template>

                <div class="flex items-center justify-between rounded-lg border border-slate-200 px-3.5 py-3">
                    <div>
                        <div class="text-sm font-semibold text-slate-800">Area Aktif</div>
                        <div class="text-xs text-slate-500">Izinkan presensi di lokasi ini</div>
                    </div>
                    <button type="button" @click="form.is_active = !form.is_active"
                            :class="form.is_active ? 'bg-brand-600' : 'bg-slate-300'"
                            class="relative h-6 w-11 flex-none rounded-full transition">
                        <span class="absolute top-0.5 h-5 w-5 rounded-full bg-white shadow transition-all"
                              :style="form.is_active ? 'left: calc(100% - 1.375rem)' : 'left: 0.125rem'"></span>
                    </button>
                </div>

                <div class="flex items-center gap-2 pt-1">
                    <button type="button" @click="save()" :disabled="saving"
                            class="inline-flex flex-1 items-center justify-center gap-2 rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700 disabled:opacity-50">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0 1 11.186 0Z"/></svg>
                        <span x-text="saving ? 'Menyimpan…' : 'Simpan Area'">Simpan Area</span>
                    </button>
                    <button type="button" @click="resetForm()" class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Reset</button>
                </div>
            </div>

            <div class="border-t border-slate-200">
                <div class="px-5 py-3 text-[11px] font-semibold uppercase tracking-wide text-slate-400">Daftar Area</div>
                <div class="max-h-56 divide-y divide-slate-100 overflow-y-auto px-2 pb-2">
                    <template x-if="areas.length === 0">
                        <div class="px-3 py-4 text-sm text-slate-400">Belum ada area.</div>
                    </template>
                    <template x-for="a in areas" :key="a.id">
                        <div class="flex items-center justify-between gap-2 rounded-lg px-3 py-2.5 hover:bg-slate-50">
                            <div class="min-w-0">
                                <div class="truncate text-sm font-semibold text-slate-800">
                                    <span x-text="a.name"></span>
                                    <span x-show="a.is_active" class="ml-1 inline-flex items-center gap-1 rounded-md bg-emerald-50 px-1.5 py-0.5 text-[10px] font-semibold text-emerald-700">Aktif</span>
                                </div>
                                <div class="text-xs text-slate-500">
                                    <span x-text="a.area_type === 'circle' ? 'Lingkaran' : 'Polygon'"></span>
                                    <template x-if="a.area_type === 'circle'"><span> · r <span x-text="a.radius_meter"></span> m</span></template>
                                </div>
                            </div>
                            <div class="flex flex-none items-center gap-1">
                                <button @click="selectArea(a)" class="rounded-md p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-700" title="Edit">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z"/></svg>
                                </button>
                                <button @click="toggleActive(a)" class="rounded-md p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-700" :title="a.is_active ? 'Nonaktifkan' : 'Aktifkan'">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5.636 5.636a9 9 0 1 0 12.728 0M12 3v9"/></svg>
                                </button>
                                <button @click="destroy(a)" class="rounded-md p-1.5 text-slate-400 hover:bg-red-50 hover:text-red-600" title="Hapus">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                                </button>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </section>
    </div>
</div>

@vite(['resources/js/admin-location.js'])
@endsection