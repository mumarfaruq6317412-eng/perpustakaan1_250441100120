<?php

namespace Tests\Feature;

use App\Models\Anggota;
use App\Models\Buku;
use App\Models\Kategori;
use App\Models\Peminjaman;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MonthlyReportAndStockTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_book_creation_rejects_future_years_and_negative_stock(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $category = Kategori::factory()->create();

        $this->followingRedirects()
            ->from(route('buku.create'))
            ->actingAs($admin)
            ->post(route('buku.store'), [
                'kategori_id' => $category->id,
                'judul' => 'Buku Masa Depan',
                'penulis' => 'Nama Penulis',
                'tahun_terbit' => now()->year + 1,
                'stok' => -1,
            ])
            ->assertOk()
            ->assertSee('Tahun terbit harus antara')
            ->assertSee('Stok tidak boleh kurang dari 0.');

        $this->assertDatabaseCount('bukus', 0);
    }

    public function test_out_of_stock_books_cannot_be_requested_and_returns_release_stock(): void
    {
        $student = User::factory()->create();
        $student->assignRole('siswa');
        $book = Buku::factory()->create(['stok' => 1]);

        $this->actingAs($student)
            ->post(route('peminjaman.store'), ['buku_id' => $book->id])
            ->assertSessionHas('success');

        $this->post(route('peminjaman.store'), ['buku_id' => $book->id])
            ->assertSessionHas('error');

        $loan = Peminjaman::firstOrFail();
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->put(route('peminjaman.setujui', $loan))
            ->assertRedirect();

        $this->put(route('pengembalian.update', $loan))
            ->assertRedirect(route('pengembalian.index'));

        $this->actingAs($student)
            ->post(route('peminjaman.store'), ['buku_id' => $book->id])
            ->assertSessionHas('success');

        $this->assertDatabaseCount('peminjamans', 2);
    }

    public function test_monthly_report_counts_logins_for_admin_and_other_roles(): void
    {
        $admin = User::factory()->create([
            'email' => 'admin-monthly@example.test',
            'password' => Hash::make('password'),
        ]);
        $admin->assignRole('admin');

        $staff = User::factory()->create([
            'email' => 'staff-monthly@example.test',
            'password' => Hash::make('password'),
        ]);
        $staff->assignRole('petugas');

        $this->post('/login', ['email' => $admin->email, 'password' => 'password'])
            ->assertRedirect();
        $this->post('/logout')->assertRedirect('/');
        $this->post('/login', ['email' => $staff->email, 'password' => 'password'])
            ->assertRedirect();

        $member = Anggota::factory()->create();
        $book = Buku::factory()->create();
        Peminjaman::factory()->create([
            'anggota_id' => $member->id,
            'buku_id' => $book->id,
            'tanggal_pinjam' => now()->toDateString(),
            'status' => 'Dipinjam',
        ]);

        $this->actingAs($admin)
            ->get(route('laporan.index'))
            ->assertOk()
            ->assertViewHas('monthlyReport', function ($monthlyReport): bool {
                $currentMonth = $monthlyReport->firstWhere('monthNumber', now()->month);

                return $currentMonth['loans'] === 1
                    && $currentMonth['adminLogins'] === 1
                    && $currentMonth['staffLogins'] === 1
                    && $currentMonth['totalLogins'] === 2;
            });

        $this->assertDatabaseHas('login_activities', ['user_id' => $admin->id, 'role' => 'admin']);
        $this->assertDatabaseHas('login_activities', ['user_id' => $staff->id, 'role' => 'petugas']);
    }

    public function test_monthly_report_rejects_a_future_year(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->get(route('laporan.index', ['year' => now()->year + 1]))
            ->assertSessionHasErrors('year');
    }
}
