@extends('layouts.app', ['user' => auth()->user()])

@section('title', 'Pengguna')

@section('content')
<div class="space-y-5">
    <header>
        <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Pengguna</h1>
        <p class="mt-1 text-sm text-slate-500">Kelola akses admin dan karyawan.</p>
    </header>

    @if(session('status'))
        <div class="rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-800">{{ session('status') }}</div>
    @endif
    @if(session('temporary_password'))
        <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
            <div class="font-semibold">Password sementara, tampilkan hanya melalui kanal aman</div>
            <code class="mt-2 block select-all rounded-lg bg-white px-3 py-2 font-mono text-sm">{{ session('temporary_password') }}</code>
            <p class="mt-2 text-xs">Password ini hanya ditampilkan sekarang. Pengguna wajib menggantinya setelah login.</p>
        </div>
    @endif

    <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6">
        <form method="get" class="flex flex-col gap-2 sm:flex-row">
            <input type="search" name="q" value="{{ $search }}" placeholder="Cari nama atau nomor pegawai..." class="min-h-11 flex-1 rounded-lg border border-slate-300 px-3 text-sm focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-600/20">
            <button type="submit" class="min-h-11 rounded-lg bg-brand-600 px-4 text-sm font-semibold text-white hover:bg-brand-700">Cari</button>
        </form>
    </section>

    <section class="overflow-x-auto rounded-2xl border border-slate-200 bg-white">
        <table class="w-full min-w-[720px] border-collapse text-sm">
            <thead>
                <tr class="border-b border-slate-100 text-left text-[11px] font-semibold uppercase tracking-wide text-slate-400">
                    <th class="px-5 py-3">No. Pegawai</th>
                    <th class="px-5 py-3">Nama</th>
                    <th class="px-5 py-3">Username</th>
                    <th class="px-5 py-3">Role</th>
                    <th class="px-5 py-3">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($users as $account)
                    <tr>
                        <td class="px-5 py-3.5 font-mono text-xs text-slate-500">{{ $account->employee_number }}</td>
                        <td class="px-5 py-3.5 font-semibold text-slate-900">{{ $account->name }}</td>
                        <td class="px-5 py-3.5 text-slate-600">{{ $account->username }}</td>
                        <td class="px-5 py-3.5">
                            <span class="rounded-md px-2 py-1 text-xs font-semibold {{ $account->isAdmin() ? 'bg-blue-50 text-blue-700' : 'bg-slate-100 text-slate-600' }}">{{ $account->role }}</span>
                        </td>
                        <td class="px-5 py-3.5">
                            <form method="post" action="{{ route('admin.users.role', $account) }}" class="flex items-center gap-2">
                                @csrf
                                @method('PATCH')
                                <select name="role" class="rounded-lg border border-slate-300 py-1.5 pl-2 pr-8 text-xs focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-600/20">
                                    <option value="employee" @selected($account->role === 'employee')>Karyawan</option>
                                    <option value="admin" @selected($account->role === 'admin')>Admin</option>
                                </select>
                                 <button type="submit" class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">Simpan</button>
                             </form>
                             @if(! $account->isSuperAdmin())
                                 <form method="post" action="{{ route('admin.users.reset-password', $account) }}" class="mt-2" onsubmit="return confirm('Buat password sementara untuk pengguna ini?')">
                                     @csrf
                                     <button type="submit" class="rounded-lg border border-amber-200 px-3 py-1.5 text-xs font-semibold text-amber-700 hover:bg-amber-50">Reset Password</button>
                                 </form>
                             @endif
                         </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-5 py-10 text-center text-sm text-slate-400">Tidak ada pengguna ditemukan.</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>
</div>
@endsection
