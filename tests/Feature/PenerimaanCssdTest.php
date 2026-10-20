<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\Support\CssdSchema;
use Tests\TestCase;

class PenerimaanCssdTest extends TestCase
{
    use CssdSchema;

    private int $itemId;

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        $this->withoutVite();
        $this->buatSchema();
        $bmhp = DB::table('master_bmhp')->insertGetId([
            'nama' => 'CPAP Uji', 'max_reuse' => 2, 'metode_steril' => 'DTT',
        ]);
        $this->itemId = DB::table('cssd_items')->insertGetId([
            'bmhp_id' => $bmhp, 'kode_unik' => 'CPAP-UJI', 'reuse_ke' => 1,
            'status' => 'KELUAR', 'last_unit' => 'POLI KULIT',
        ]);
        $this->actingAs(User::factory()->create(['role' => 'user_cssd']));
    }

    public function test_antrian_hanya_transaksi_terbaru_belum_diterima_tanpa_menghapus_riwayat(): void
    {
        $lama = $this->distribusi(['tanggal_uji_perawat' => '2026-08-10', 'no_rm' => 'RM-LAMA']);
        $this->postJson('/barang-masuk/simpan', $this->penerimaan($lama))->assertOk();
        DB::table('cssd_items')->where('id', $this->itemId)->update(['status' => 'KELUAR']);
        $baru = $this->distribusi(['no_rm' => 'RM-BARU']);

        $this->getJson('/operasional/perawat-selesai-data')->assertOk()->assertJsonCount(1)
            ->assertJsonPath('0.cssd_keluar_log_id', $baru);
        $this->getJson('/operasional/perawat-selesai-data?draw=1&length=1&start=0&search[value]=CPAP-UJI')
            ->assertOk()->assertJsonPath('recordsTotal', 1)->assertJsonPath('recordsFiltered', 1)
            ->assertJsonCount(1, 'data')->assertJsonPath('data.0.cssd_keluar_log_id', $baru);
        $this->getJson('/operasional/perawat-selesai-data?draw=2&search[value]=RM-LAMA')
            ->assertOk()->assertJsonPath('recordsTotal', 1)->assertJsonPath('recordsFiltered', 0);
        $this->getJson('/operasional/perawat-selesai-data?draw=3&length=1&start=1')
            ->assertOk()->assertJsonCount(0, 'data');
        $this->assertDatabaseCount('cssd_keluar_logs', 2);
        $this->assertDatabaseHas('cssd_masuk_logs', ['cssd_keluar_log_id' => $lama]);
    }

    public function test_penilaian_lama_yang_belum_diterima_juga_tidak_muncul_pada_siklus_baru(): void
    {
        $this->distribusi();
        $baru = $this->distribusi();
        $this->getJson('/operasional/perawat-selesai-data')->assertJsonCount(1)
            ->assertJsonPath('0.cssd_keluar_log_id', $baru);
        foreach ([null, 'TIDAK LAYAK'] as $hasil) {
            DB::table('cssd_keluar_logs')->where('id', $baru)->update(['hasil_uji_perawat' => $hasil]);
            $this->getJson('/operasional/perawat-selesai-data?draw=1')->assertOk()->assertJsonPath('recordsTotal', 0);
            $this->postJson('/barang-masuk/simpan', $this->penerimaan($baru))->assertUnprocessable();
        }
        $this->assertDatabaseCount('cssd_masuk_logs', 0);
    }

    public function test_scan_dan_pilih_mengacu_ke_transaksi_yang_sama_dan_menolak_baris_lama(): void
    {
        $lama = $this->distribusi();
        $baru = $this->distribusi(['no_rm' => 'RM-BARU']);
        $url = '/operasional/item-kode/CPAP-UJI';
        $this->getJson($url)->assertOk()->assertJsonPath('keluar_terakhir.id', $baru);
        $this->getJson($url . '?cssd_keluar_log_id=' . $baru)->assertOk()
            ->assertJsonPath('keluar_terakhir.id', $baru)->assertJsonPath('keluar_terakhir.no_rm', 'RM-BARU');
        $this->getJson($url . '?cssd_keluar_log_id=' . $lama)->assertUnprocessable();
        $this->getJson($url . '?cssd_keluar_log_id[]=1')->assertUnprocessable();
    }

    public function test_simpan_wajib_transaksi_terbaru_milik_item_yang_dipilih(): void
    {
        $lama = $this->distribusi();
        $baru = $this->distribusi();
        $itemLain = DB::table('cssd_items')->insertGetId([
            'bmhp_id' => DB::table('cssd_items')->value('bmhp_id'),
            'kode_unik' => 'ALAT-LAIN', 'status' => 'KELUAR',
        ]);
        $logLain = $this->distribusi(['cssd_item_id' => $itemLain]);
        foreach ([null, $lama, $logLain, 9999] as $id) {
            $this->postJson('/barang-masuk/simpan', $this->penerimaan($id))->assertUnprocessable();
        }
        $this->assertDatabaseCount('cssd_masuk_logs', 0);
        $this->assertDatabaseCount('cssd_sterilisasi_logs', 0);
        $this->assertDatabaseCount('cssd_logs', 0);
        $this->assertDatabaseHas('cssd_items', ['id' => $this->itemId, 'status' => 'KELUAR', 'reuse_ke' => 1]);
        $this->postJson('/barang-masuk/simpan', $this->penerimaan($baru))->assertOk()->assertJsonPath('reuse_ke', 2);
    }

    public function test_penerimaan_hanya_sekali_dan_reuse_kedua_tetap_ready(): void
    {
        $id = $this->distribusi();
        $data = $this->penerimaan($id);
        $this->postJson('/barang-masuk/simpan', $data)->assertOk()->assertJsonPath('status', 'READY')->assertJsonPath('reuse_ke', 2);
        $this->postJson('/barang-masuk/simpan', $data)->assertUnprocessable();
        $this->getJson('/operasional/perawat-selesai-data')->assertExactJson([]);

        // Bahkan jika status lama tidak konsisten, transaksi yang sudah diterima tetap ditolak.
        DB::table('cssd_items')->where('id', $this->itemId)->update(['status' => 'KELUAR']);
        $this->getJson('/operasional/perawat-selesai-data')->assertExactJson([]);
        $this->getJson('/operasional/item-kode/CPAP-UJI')->assertUnprocessable();
        $this->postJson('/barang-masuk/simpan', $data)->assertUnprocessable()
            ->assertJsonPath('message', 'Transaksi ini sudah diterima CSSD dan tidak dapat diproses kembali.');
        $this->assertDatabaseCount('cssd_masuk_logs', 1);
        $this->assertDatabaseCount('cssd_sterilisasi_logs', 1);
        $this->assertDatabaseCount('cssd_logs', 2);
        $this->assertDatabaseHas('cssd_items', ['id' => $this->itemId, 'reuse_ke' => 2]);
    }

    public function test_form_lama_tidak_bisa_menerima_siklus_baru_secara_diam_diam(): void
    {
        $lama = $this->distribusi();
        $this->postJson('/barang-masuk/simpan', $this->penerimaan($lama))->assertOk();
        DB::table('cssd_items')->where('id', $this->itemId)->update(['status' => 'KELUAR']);
        $baru = $this->distribusi();
        $this->postJson('/barang-masuk/simpan', $this->penerimaan($lama))->assertUnprocessable();
        $this->assertDatabaseMissing('cssd_masuk_logs', ['cssd_keluar_log_id' => $baru]);
        $this->assertDatabaseHas('cssd_items', ['id' => $this->itemId, 'status' => 'KELUAR', 'reuse_ke' => 2]);
    }

    public function test_kegagalan_sterilisasi_membatalkan_seluruh_penerimaan_dan_increment_reuse(): void
    {
        $id = $this->distribusi();
        DB::unprepared("CREATE TRIGGER gagal_steril BEFORE INSERT ON cssd_sterilisasi_logs BEGIN SELECT RAISE(ABORT, 'Simulasi gagal steril'); END");
        $this->postJson('/barang-masuk/simpan', $this->penerimaan($id))->assertStatus(500);
        $this->assertDatabaseCount('cssd_masuk_logs', 0);
        $this->assertDatabaseCount('cssd_sterilisasi_logs', 0);
        $this->assertDatabaseCount('cssd_logs', 0);
        $this->assertDatabaseHas('cssd_items', ['id' => $this->itemId, 'status' => 'KELUAR', 'reuse_ke' => 1]);
        DB::unprepared('DROP TRIGGER gagal_steril');
        $this->postJson('/barang-masuk/simpan', $this->penerimaan($id))->assertOk()->assertJsonPath('reuse_ke', 2);
    }

    public function test_dirty_awal_tanpa_distribusi_dan_pasien_tetap_bisa_diterima_tanpa_increment(): void
    {
        DB::table('cssd_items')->where('id', $this->itemId)->update(['status' => 'DIRTY']);
        $this->getJson('/operasional/item-kode/CPAP-UJI')->assertOk()->assertJsonPath('keluar_terakhir', null);
        $this->postJson('/barang-masuk/simpan', $this->penerimaan(9999))->assertUnprocessable();
        $this->postJson('/barang-masuk/simpan', $this->penerimaan(null))->assertOk()
            ->assertJsonPath('status', 'READY')->assertJsonPath('reuse_ke', 1);
        $this->assertDatabaseHas('cssd_masuk_logs', ['cssd_keluar_log_id' => null, 'no_rm' => null, 'unit_asal' => 'POLI KULIT']);
        $this->postJson('/barang-masuk/simpan', $this->penerimaan(null))->assertUnprocessable();
        $this->assertDatabaseCount('cssd_masuk_logs', 1);
    }

    public function test_maksimal_reuse_tanpa_approval_menjadi_expired_sekali(): void
    {
        DB::table('cssd_items')->where('id', $this->itemId)->update(['reuse_ke' => 2]);
        $id = $this->distribusi();
        $data = array_replace($this->penerimaan($id), [
            'metode_steril' => null, 'tanggal_steril' => null, 'masa_expire_bulan' => null,
            'petugas_pengemasan' => null, 'petugas_sterilisasi' => null,
        ]);
        $this->postJson('/barang-masuk/simpan', $data)->assertOk()->assertJsonPath('status', 'EXPIRED')->assertJsonPath('reuse_ke', 2);
        $this->postJson('/barang-masuk/simpan', $data)->assertUnprocessable();
        $this->assertDatabaseCount('cssd_masuk_logs', 1);
        $this->assertDatabaseCount('cssd_sterilisasi_logs', 0);
    }

    public function test_approval_over_max_tetap_bisa_diproses(): void
    {
        DB::table('cssd_items')->where('id', $this->itemId)->update(['reuse_ke' => 2]);
        $id = $this->distribusi([
            'approval_over_reuse' => true, 'approval_dpjp' => 'Dokter Uji', 'approval_alasan' => 'Persetujuan uji',
        ]);
        $this->postJson('/barang-masuk/simpan', $this->penerimaan($id))->assertOk()->assertJsonPath('status', 'READY')->assertJsonPath('reuse_ke', 3);
        $this->assertDatabaseCount('cssd_masuk_logs', 1);
    }

    public function test_status_selain_keluar_tidak_muncul_dan_perawat_tidak_bisa_menerima(): void
    {
        $id = $this->distribusi();
        foreach (['READY', 'DIRTY', 'DISPOSE', 'EXPIRED'] as $status) {
            DB::table('cssd_items')->where('id', $this->itemId)->update(['status' => $status]);
            $this->getJson('/operasional/perawat-selesai-data')->assertExactJson([]);
            $this->postJson('/barang-masuk/simpan', $this->penerimaan($id))->assertUnprocessable();
        }
        DB::table('cssd_items')->where('id', $this->itemId)->update(['status' => 'KELUAR']);
        $this->actingAs(User::factory()->create(['role' => 'user_perawat']));
        $this->postJson('/barang-masuk/simpan', $this->penerimaan($id))->assertForbidden();
        $this->assertDatabaseCount('cssd_masuk_logs', 0);
    }

    private function distribusi(array $perubahan = []): int
    {
        return DB::table('cssd_keluar_logs')->insertGetId(array_replace([
            'cssd_item_id' => $this->itemId, 'tanggal_keluar' => '2026-10-19', 'jam_keluar' => '08:00',
            'tanggal_penggunaan' => '2026-10-20', 'tanggal_uji_perawat' => '2026-10-20',
            'nama_section_pengguna' => 'POLI KULIT', 'no_rm' => 'RM-UJI', 'nama_pasien' => 'Pasien Uji',
            'nama_dpjp' => 'Dokter Uji', 'nama_perawat' => 'Perawat Uji', 'petugas' => 'CSSD Uji',
            'reuse_ke_keluar' => 2, 'hasil_uji_perawat' => 'LAYAK',
        ], $perubahan));
    }

    private function penerimaan(?int $id): array
    {
        return [
            'cssd_item_id' => $this->itemId, 'cssd_keluar_log_id' => $id,
            'tanggal_masuk' => '2026-10-20', 'metode_steril' => 'DTT', 'tanggal_steril' => '2026-10-20',
            'masa_expire_bulan' => 6, 'petugas_penerima_pencucian' => 'Penerima Uji',
            'petugas_pengemasan' => 'Pengemasan Uji', 'petugas_sterilisasi' => 'Sterilisasi Uji',
        ];
    }
}
