<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBukuRequest;
use App\Models\Buku;
use App\Models\Kategori;
use App\Models\Peminjaman;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BukuController extends Controller
{
    // Menampilkan halaman index admin (tabel daftar buku)
    public function index()
    {
        $bukuList = Buku::with('kategori')->get();

        return view('buku.index', compact('bukuList'));
    }

    public function cari(Request $request)
    {
        $validated = $request->validate([
            'judul' => ['required', 'string', 'max:150'],
        ]);

        // (Sesuaikan nama tabel database Anda, misal 'bukus' atau 'buku')
        $judul = $validated['judul'];
        $results = DB::select('SELECT * FROM bukus WHERE judul = ?', [$judul]);

        return response()->json($results);
    }

    // Menampilkan form tambah buku baru
    public function create()
    {
        $kategoriList = Kategori::all();

        return view('buku.create', compact('kategoriList'));
    }

    // Menyimpan data buku baru menggunakan StoreBukuRequest
    public function store(StoreBukuRequest $request)
    {
        // Data sudah otomatis tervalidasi dengan aman di sini!
        Buku::create($request->validated());

        return redirect()->route('buku.index')->with('success', 'Data buku berhasil ditambahkan.');
    }

    // Menampilkan katalog buku untuk siswa / umum
    public function katalog()
    {
        $bukuList = Buku::with('kategori')
            ->withCount(['peminjaman as peminjaman_aktif_count' => function ($query): void {
                $query->whereIn('status', ['Pending', 'Diajukan', 'Dipinjam']);
            }])
            ->get();

        return view('buku.katalog', compact('bukuList'));
    }

    // Menampilkan detail data buku (Show)
    public function show(int $id)
    {
        $buku = Buku::with('kategori')->findOrFail($id);

        return view('buku.show', compact('buku'));
    }

    // Menampilkan halaman form edit
    public function edit(int $id)
    {
        $buku = Buku::findOrFail($id);
        $kategoriList = Kategori::all();

        return view('buku.edit', compact('buku', 'kategoriList'));
    }

    // Memperbarui data buku (Update)
    public function update(Request $request, int $id)
    {
        $request->validate([
            'kategori_id' => 'required|exists:kategoris,id',
            'judul' => 'required|string|max:150',
            'penulis' => 'required|string|max:100',
            'tahun_terbit' => 'required|integer|between:1900,'.date('Y'),
            'stok' => 'sometimes|integer|min:0',
        ]);

        if ($request->has('stok')) {
            $jumlahPeminjamanAktif = Peminjaman::where('buku_id', $id)
                ->whereIn('status', ['Pending', 'Diajukan', 'Dipinjam'])
                ->count();

            if ($request->integer('stok') < $jumlahPeminjamanAktif) {
                return back()
                    ->withErrors(['stok' => 'Stok tidak boleh kurang dari jumlah buku yang sedang dipinjam atau menunggu persetujuan.'])
                    ->withInput();
            }
        }

        $buku = Buku::findOrFail($id);
        $buku->update($request->all());

        return redirect()->route('buku.index')->with('success', 'Data buku berhasil diperbarui!');
    }

    // Menghapus data buku (Delete)

    public function destroy(int $id)
    {
        $buku = Buku::findOrFail($id);
        $buku->delete();

        return redirect()->route('buku.index')->with('success', 'Buku berhasil dihapus dari sistem.');
    }
}
