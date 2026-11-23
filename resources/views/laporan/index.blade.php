<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Laporan Perpustakaan') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <section class="rounded-xl border border-gray-100 bg-white p-6 shadow-sm">
                <form action="{{ route('laporan.index') }}" method="GET" class="flex flex-wrap items-end gap-3">
                    <div>
                        <label for="year" class="block text-sm font-medium text-gray-700">Tahun laporan</label>
                        <input id="year" name="year" type="number" min="1900" max="{{ now()->year }}" value="{{ $year }}" required
                            class="mt-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @error('year')
                            <p class="mt-1 text-sm text-red-600" role="alert">{{ $message }}</p>
                        @enderror
                    </div>
                    <button type="submit" class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                        Tampilkan
                    </button>
                </form>
            </section>

            <section aria-label="Ringkasan peminjaman" class="grid grid-cols-2 gap-4 lg:grid-cols-5">
                @foreach ([
                    'total' => 'Total Peminjaman',
                    'menunggu' => 'Menunggu Persetujuan',
                    'dipinjam' => 'Sedang Dipinjam',
                    'dikembalikan' => 'Dikembalikan',
                    'ditolak' => 'Ditolak',
                ] as $key => $label)
                    <div class="rounded-xl border border-gray-100 bg-white p-5 shadow-sm">
                        <p class="text-sm text-gray-500">{{ $label }}</p>
                        <p class="mt-2 text-2xl font-bold text-gray-900">{{ number_format($stats[$key]) }}</p>
                    </div>
                @endforeach
            </section>

            <section class="overflow-hidden rounded-xl border border-gray-100 bg-white p-6 shadow-sm">
                <h3 class="mb-5 text-lg font-semibold text-gray-900">Laporan Bulanan {{ $year }}</h3>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[850px] text-left text-sm">
                        <thead class="bg-blue-50 text-xs uppercase text-gray-700">
                            <tr>
                                <th class="px-4 py-3">Bulan</th>
                                <th class="px-4 py-3">Peminjaman</th>
                                <th class="px-4 py-3">Login Admin</th>
                                <th class="px-4 py-3">Login Petugas</th>
                                <th class="px-4 py-3">Login Siswa</th>
                                <th class="px-4 py-3">Login Role Lain</th>
                                <th class="px-4 py-3">Total Login</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach ($monthlyReport as $month)
                                <tr>
                                    <td class="px-4 py-3 font-medium text-gray-900">{{ $month['month'] }}</td>
                                    <td class="px-4 py-3">{{ number_format($month['loans']) }}</td>
                                    <td class="px-4 py-3">{{ number_format($month['adminLogins']) }}</td>
                                    <td class="px-4 py-3">{{ number_format($month['staffLogins']) }}</td>
                                    <td class="px-4 py-3">{{ number_format($month['studentLogins']) }}</td>
                                    <td class="px-4 py-3">{{ number_format($month['otherLogins']) }}</td>
                                    <td class="px-4 py-3 font-semibold">{{ number_format($month['totalLogins']) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="overflow-hidden rounded-xl border border-gray-100 bg-white p-6 shadow-sm">
                <h3 class="mb-5 text-lg font-semibold text-gray-900">Rekap Peminjaman Buku</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-blue-50 text-xs uppercase text-gray-700">
                            <tr>
                                <th class="px-4 py-3">Anggota</th>
                                <th class="px-4 py-3">Buku</th>
                                <th class="px-4 py-3">Tanggal Pinjam</th>
                                <th class="px-4 py-3">Tanggal Kembali</th>
                                <th class="px-4 py-3">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse ($peminjamanList as $peminjaman)
                                <tr>
                                    <td class="px-4 py-3">{{ $peminjaman->anggota->nama ?? '-' }}</td>
                                    <td class="px-4 py-3 font-medium text-gray-900">{{ $peminjaman->buku->judul ?? '-' }}</td>
                                    <td class="px-4 py-3">{{ $peminjaman->tanggal_pinjam }}</td>
                                    <td class="px-4 py-3">{{ $peminjaman->tanggal_kembali ?? '-' }}</td>
                                    <td class="px-4 py-3">{{ $peminjaman->status }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-8 text-center text-gray-500">Belum ada data peminjaman untuk dilaporkan.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
