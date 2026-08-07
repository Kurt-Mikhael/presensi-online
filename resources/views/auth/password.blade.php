@extends('layouts.app', ['user' => auth()->user()])

@section('title', 'Ganti Password')

@section('content')
<div class="mx-auto max-w-xl space-y-5">
    <header>
        <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Ganti Password</h1>
        <p class="mt-1 text-sm text-slate-500">Gunakan password yang unik dan tidak digunakan di layanan lain.</p>
    </header>

    @if(session('status'))
        <div class="rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-800">{{ session('status') }}</div>
    @endif

    @if($errors->any())
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $errors->first() }}</div>
    @endif

    <form method="post" action="{{ route('password.update') }}" class="space-y-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
        @csrf
        @method('PUT')

        <div>
            <label for="current_password" class="block text-sm font-medium text-slate-700">Password saat ini</label>
            <input id="current_password" name="current_password" type="password" autocomplete="current-password" required class="mt-1.5 block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-600/20">
        </div>

        <div>
            <label for="password" class="block text-sm font-medium text-slate-700">Password baru</label>
            <input id="password" name="password" type="password" autocomplete="new-password" required class="mt-1.5 block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-600/20">
        </div>

        <div>
            <label for="password_confirmation" class="block text-sm font-medium text-slate-700">Konfirmasi password baru</label>
            <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required class="mt-1.5 block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-600/20">
        </div>

        <button type="submit" class="inline-flex min-h-11 w-full items-center justify-center rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700">Simpan Password Baru</button>
    </form>
</div>
@endsection
