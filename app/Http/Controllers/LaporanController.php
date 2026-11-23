<?php

namespace App\Http\Controllers;

use App\Models\LoginActivity;
use App\Models\Peminjaman;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class LaporanController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'year' => ['sometimes', 'integer', 'between:1900,'.now()->year],
        ]);
        $year = (int) ($validated['year'] ?? now()->year);
        $yearStart = Carbon::create($year, 1, 1)->startOfYear();
        $yearEnd = $yearStart->copy()->endOfYear();

        $peminjamanList = Peminjaman::with(['anggota', 'buku'])
            ->latest()
            ->get();

        $peminjamanPerBulan = Peminjaman::query()
            ->whereBetween('tanggal_pinjam', [$yearStart->toDateString(), $yearEnd->toDateString()])
            ->get(['tanggal_pinjam'])
            ->groupBy(fn (Peminjaman $peminjaman): int => (int) Carbon::parse($peminjaman->tanggal_pinjam)->format('n'));

        $loginPerBulan = LoginActivity::query()
            ->whereBetween('logged_in_at', [$yearStart, $yearEnd])
            ->get(['role', 'logged_in_at'])
            ->groupBy(fn (LoginActivity $activity): int => $activity->logged_in_at->month);

        $monthlyReport = collect(range(1, 12))->map(function (int $month) use ($peminjamanPerBulan, $loginPerBulan, $year): array {
            $loginByRole = $loginPerBulan->get($month, collect())->countBy('role');

            return [
                'monthNumber' => $month,
                'month' => Carbon::create($year, $month, 1)->translatedFormat('F'),
                'loans' => $peminjamanPerBulan->get($month, collect())->count(),
                'adminLogins' => (int) $loginByRole->get('admin', 0),
                'staffLogins' => (int) $loginByRole->get('petugas', 0),
                'studentLogins' => (int) $loginByRole->get('siswa', 0),
                'otherLogins' => (int) $loginByRole->except(['admin', 'petugas', 'siswa'])->sum(),
                'totalLogins' => (int) $loginByRole->sum(),
            ];
        });

        $stats = [
            'total' => $peminjamanList->count(),
            'menunggu' => $peminjamanList->whereIn('status', ['Pending', 'Diajukan'])->count(),
            'dipinjam' => $peminjamanList->where('status', 'Dipinjam')->count(),
            'dikembalikan' => $peminjamanList->where('status', 'Dikembalikan')->count(),
            'ditolak' => $peminjamanList->where('status', 'Ditolak')->count(),
        ];

        return view('laporan.index', compact('peminjamanList', 'stats', 'monthlyReport', 'year'));
    }
}
