<?php

namespace App\Http\Requests;

use App\Models\Peminjaman;
use Illuminate\Validation\Validator;

class UpdateBukuRequest extends StoreBukuRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;

    }

    public function rules(): array
    {
        $tahunSekarang = now()->year;

        return [
            'kategori_id' => 'sometimes|required|exists:kategoris,id',
            'judul' => 'sometimes|required|string|min:3|max:150',
            'penulis' => 'sometimes|required|string|min:3|max:100',
            'tahun_terbit' => "sometimes|required|integer|between:1900,{$tahunSekarang}",
            'stok' => 'sometimes|integer|min:0',
        ];
    }

    /**
     * @return array<int, \Closure(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty() || ! $this->exists('stok')) {
                    return;
                }

                $bookId = $this->route('buku');

                if (! is_numeric($bookId)) {
                    return;
                }

                $activeLoans = Peminjaman::query()
                    ->where('buku_id', $bookId)
                    ->whereIn('status', ['Pending', 'Diajukan', 'Dipinjam'])
                    ->count();

                if ((int) $this->input('stok') < $activeLoans) {
                    $validator->errors()->add(
                        'stok',
                        'Stok tidak boleh kurang dari jumlah buku yang sedang dipinjam atau menunggu persetujuan.',
                    );
                }
            },
        ];
    }
}
