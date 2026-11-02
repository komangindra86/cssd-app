<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\Support\CssdSchema;
use Tests\TestCase;

class RuanganSimrsTest extends TestCase
{
    use CssdSchema;

    private int $bmhpId;

    protected function setUp(): void
    {
        parent::setUp();

        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        $this->withoutVite();
        $this->buatSchema();
        // Seperti di SIMRS asli: tidak ada ruangan bernama "NICU" atau "Perina".
        $this->fakeRuanganSimrs(['IGD', 'PERINATOLOGI', 'RAWAT INAP PICU/NICU']);
        $this->bmhpId = DB::table('master_bmhp')->insertGetId([
            'nama' => 'Selang Neopuff', 'max_reuse' => 20, 'metode_steril' => 'DTT',
        ]);
        $this->actingAs(User::factory()->create(['role' => 'user_cssd', 'name' => 'Petugas CSSD']));
    }

    public function test_item_alat_menolak_ruangan_ketik_bebas_dan_menyimpan_nama_resmi(): void
    {
        $this->postJson('/item-alat/tambah', $this->formItem(['last_unit' => 'nicu']))
            ->assertUnprocessable()->assertJsonValidationErrors('last_unit');
        $this->assertDatabaseCount('cssd_items', 0);

        $this->postJson('/item-alat/tambah', $this->formItem(['last_unit' => '  rawat inap   picu/nicu ']))
            ->assertOk()->assertJsonPath('item.last_unit', 'RAWAT INAP PICU/NICU');
    }

    public function test_ruangan_item_alat_wajib_diisi_kecuali_alat_keluar(): void
    {
        foreach ([null, '', '   '] as $kosong) {
            $this->postJson('/item-alat/tambah', $this->formItem(['kode_unik' => 'NCU-SLNP-02', 'last_unit' => $kosong]))
                ->assertUnprocessable()->assertJsonPath('errors.last_unit.0', 'Ruangan wajib dipilih dari daftar SIMRS.');
        }
        $this->assertDatabaseCount('cssd_items', 0);

        $lama = $this->buatItem('READY', null);
        $this->putJson("/item-alat/edit/{$lama}", $this->formItem(['kode_unik' => 'NCU-1', 'last_unit' => null]))
            ->assertUnprocessable()->assertJsonValidationErrors('last_unit');
        $this->putJson("/item-alat/edit/{$lama}", $this->formItem(['kode_unik' => 'NCU-1', 'last_unit' => 'igd']))
            ->assertOk()->assertJsonPath('item.last_unit', 'IGD');

        // Ruangan alat KELUAR terkunci, jadi data lama tanpa ruangan tetap bisa diedit kode uniknya.
        $keluar = $this->buatItem('KELUAR', null);
        $this->putJson("/item-alat/edit/{$keluar}", $this->formItem(['kode_unik' => 'NCU-KELUAR', 'status' => 'KELUAR', 'last_unit' => null]))
            ->assertOk();
        $this->assertDatabaseHas('cssd_items', ['id' => $keluar, 'kode_unik' => 'NCU-KELUAR', 'last_unit' => null]);
    }

    public function test_edit_item_lama_wajib_memperbaiki_ruangan_kecuali_alat_keluar(): void
    {
        $ready = $this->buatItem('READY', 'Perina');
        $keluar = $this->buatItem('KELUAR', 'nicu');

        $this->putJson("/item-alat/edit/{$ready}", $this->formItem(['kode_unik' => 'NCU-1', 'status' => 'READY', 'last_unit' => 'Perina']))
            ->assertUnprocessable()->assertJsonValidationErrors('last_unit');
        $this->putJson("/item-alat/edit/{$ready}", $this->formItem(['kode_unik' => 'NCU-1', 'status' => 'READY', 'last_unit' => 'perinatologi']))
            ->assertOk()->assertJsonPath('item.last_unit', 'PERINATOLOGI');

        // Ruangan alat KELUAR terkunci, jadi data lama tidak menghalangi edit kode unik.
        $this->putJson("/item-alat/edit/{$keluar}", $this->formItem(['kode_unik' => 'NCU-BARU', 'status' => 'KELUAR', 'last_unit' => 'nicu']))
            ->assertOk();
        $this->assertDatabaseHas('cssd_items', ['id' => $keluar, 'kode_unik' => 'NCU-BARU', 'last_unit' => 'nicu']);
    }

    public function test_distribusi_menolak_ruangan_ketik_bebas_dan_menyimpan_nama_resmi(): void
    {
        $id = $this->buatItem('READY', 'PERINATOLOGI');

        $this->postJson('/barang-keluar/simpan', $this->formKeluar($id, 'nicu'))
            ->assertUnprocessable()->assertJsonValidationErrors('nama_section_pengguna');
        $this->assertDatabaseHas('cssd_items', ['id' => $id, 'status' => 'READY', 'last_unit' => 'PERINATOLOGI']);
        $this->assertDatabaseCount('cssd_keluar_logs', 0);

        $this->postJson('/barang-keluar/simpan', $this->formKeluar($id, 'Rawat Inap PICU/NICU'))->assertOk();
        $this->assertDatabaseHas('cssd_items', ['id' => $id, 'status' => 'KELUAR', 'last_unit' => 'RAWAT INAP PICU/NICU']);
        $this->assertDatabaseHas('cssd_keluar_logs', ['cssd_item_id' => $id, 'nama_section_pengguna' => 'RAWAT INAP PICU/NICU']);
    }

    public function test_sterilisasi_awal_menolak_ruangan_ketik_bebas(): void
    {
        $id = $this->buatItem('DIRTY', null);

        $this->postJson('/barang-masuk/simpan', $this->formSterilisasiAwal($id, 'Perina'))
            ->assertUnprocessable()->assertJsonValidationErrors('nama_section_pengguna');
        $this->assertDatabaseHas('cssd_items', ['id' => $id, 'status' => 'DIRTY']);

        $this->postJson('/barang-masuk/simpan', $this->formSterilisasiAwal($id, 'perinatologi'))->assertOk();
        $this->assertDatabaseHas('cssd_items', ['id' => $id, 'status' => 'READY', 'last_unit' => 'PERINATOLOGI']);
    }

    public function test_daftar_ruangan_di_cache_dan_memakai_cadangan_saat_simrs_gangguan(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'super_admin']));
        $this->getJson('/operasional/get-ruangan')->assertOk()->assertJsonCount(3, 'ruangan')
            ->assertJsonPath('ruangan.2', ['id' => '902', 'nama' => 'RAWAT INAP PICU/NICU', 'departemen_id' => '16']);
        $this->getJson('/operasional/get-ruangan')->assertOk();
        Http::assertSentCount(1);

        Cache::forget('simrs.ruangan');
        $this->responsRuanganSimrs = fn () => throw new ConnectionException('SIMRS gangguan');
        $this->getJson('/operasional/get-ruangan')->assertOk()->assertJsonCount(3, 'ruangan');
        $this->postJson('/item-alat/tambah', $this->formItem(['last_unit' => 'IGD']))->assertOk();
    }

    public function test_simrs_gangguan_tanpa_cadangan_menolak_simpan_dengan_pesan_jelas(): void
    {
        $this->responsRuanganSimrs = Http::response('Server Error', 500);

        $this->postJson('/item-alat/tambah', $this->formItem(['last_unit' => 'IGD']))
            ->assertUnprocessable()
            ->assertJsonPath('errors.last_unit.0', 'Daftar ruangan SIMRS tidak bisa dimuat. Coba lagi beberapa saat.');
        $this->assertDatabaseCount('cssd_items', 0);
    }

    private function buatItem(string $status, ?string $ruangan): int
    {
        return DB::table('cssd_items')->insertGetId([
            'bmhp_id' => $this->bmhpId, 'kode_unik' => 'NCU-' . (DB::table('cssd_items')->count() + 1),
            'status' => $status, 'last_unit' => $ruangan, 'reuse_ke' => 3,
        ]);
    }

    private function formItem(array $overrides = []): array
    {
        return array_replace([
            'bmhp_id' => $this->bmhpId, 'kode_unik' => 'NCU-SLNP-01', 'reuse_ke' => 3,
            'status' => 'READY', 'last_unit' => 'IGD',
        ], $overrides);
    }

    private function formKeluar(int $id, string $ruangan): array
    {
        return [
            'cssd_item_ids' => [$id], 'tanggal_keluar' => '2026-11-02', 'jam_keluar' => '08:00',
            'nama_section_pengguna' => $ruangan, 'petugas' => 'Petugas CSSD', 'perawat_penerima' => 'Perawat Uji',
        ];
    }

    private function formSterilisasiAwal(int $id, string $ruangan): array
    {
        return [
            'cssd_item_id' => $id, 'tanggal_masuk' => '2026-11-02', 'nama_section_pengguna' => $ruangan,
            'metode_steril' => 'DTT', 'tanggal_steril' => '2026-11-02', 'masa_expire_bulan' => 3,
            'petugas_penerima_pencucian' => 'Petugas A', 'petugas_pengemasan' => 'Petugas B', 'petugas_sterilisasi' => 'Petugas C',
        ];
    }
}
