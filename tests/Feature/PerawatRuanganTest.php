<?php

namespace Tests\Feature;

use App\Http\Controllers\OperasionalCssdController;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PerawatRuanganTest extends TestCase
{
    private int $bmhpId;

    protected function setUp(): void
    {
        parent::setUp();

        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        $this->withoutVite();
        $this->buatSchema();
        $this->bmhpId = DB::table('master_bmhp')->insertGetId([
            'nama' => 'CPAP Uji', 'max_reuse' => 2, 'metode_steril' => 'DTT',
        ]);
    }

    public function test_perawat_hanya_melihat_bmhp_ruangannya_dengan_pencarian_dan_pagination(): void
    {
        $igd = $this->buatDistribusi('IGD');
        $igdKedua = $this->buatDistribusi(' igd ');
        $this->buatDistribusi('SANDAT');
        $this->buatDistribusi('IGD TIMUR');
        $this->buatDistribusi(null);
        $this->actingAs($this->perawat());

        $this->getJson('/operasional/keluar-data')->assertOk()->assertJsonCount(2)
            ->assertJsonFragment(['cssd_keluar_log_id' => $igd['log']]);

        $this->getJson('/operasional/keluar-data?draw=1&length=1&start=0&belum_uji=1')
            ->assertOk()->assertJsonPath('recordsTotal', 2)->assertJsonPath('recordsFiltered', 2)
            ->assertJsonCount(1, 'data')->assertJsonPath('data.0.cssd_keluar_log_id', $igdKedua['log']);
        $this->getJson('/operasional/keluar-data?draw=2&length=1&start=1&belum_uji=1')
            ->assertOk()->assertJsonPath('data.0.cssd_keluar_log_id', $igd['log']);

        $this->getJson('/operasional/keluar-data?draw=3&search[value]=SANDAT')
            ->assertOk()->assertJsonPath('recordsTotal', 2)->assertJsonPath('recordsFiltered', 0)->assertJsonCount(0, 'data');
        $this->getJson('/operasional/keluar-data?nama_section_pengguna=SANDAT')->assertOk()->assertExactJson([]);
        $this->getJson('/operasional/keluar-data?nama_section_pengguna=%25')->assertOk()->assertJsonCount(2);
    }

    public function test_dashboard_perawat_dibatasi_tetapi_cssd_dan_admin_tetap_semua_ruangan(): void
    {
        $this->buatDistribusi('IGD', 'READY');
        $this->buatDistribusi('SANDAT', 'READY');
        $this->buatDistribusi('SANDAT', 'DISPOSE');
        $this->buatDistribusi('IGD');

        $this->actingAs($this->perawat())->getJson('/operasional/dashboard-data')->assertOk()
            ->assertExactJson(['ready' => 1, 'keluar' => 1, 'expired' => 0, 'dispose' => 0]);

        foreach (['user_cssd', 'super_admin', 'admin', 'user'] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role, 'nama_ruangan' => 'IGD']))
                ->getJson('/operasional/dashboard-data')->assertOk()
                ->assertExactJson(['ready' => 2, 'keluar' => 1, 'expired' => 0, 'dispose' => 1]);
        }
    }

    public function test_akun_belum_diatur_tidak_mendapat_akses_semua_ruangan(): void
    {
        $item = $this->buatDistribusi('IGD');
        $user = $this->perawat(['ruangan_id' => null, 'nama_ruangan' => null]);
        $this->actingAs($user);
        $this->getJson('/operasional/keluar-data')->assertOk()->assertExactJson([]);
        $this->getJson('/operasional/get-ruangan')->assertOk()->assertJsonCount(0, 'ruangan')->assertJsonCount(0, 'data');
        $this->getJson('/operasional/dashboard-data')->assertOk()
            ->assertExactJson(['ready' => 0, 'keluar' => 0, 'expired' => 0, 'dispose' => 0]);
        $this->postJson('/input-perawat/simpan', $this->penilaian([$item['log']]))->assertForbidden();
        $this->get('/input-perawat')->assertOk()->assertSee('Ruangan akun belum diatur.');

        $user->update(['nama_ruangan' => 'IGD']);
        $this->actingAs($user->fresh())->getJson('/operasional/keluar-data')->assertExactJson([]);
    }

    public function test_ruangan_perawat_otomatis_terisi_dan_api_tidak_memuat_ruangan_lain(): void
    {
        $this->actingAs($this->perawat());
        $this->get('/input-perawat')->assertOk()->assertSee('value="IGD" readonly', false);
        $ruangan = [['id' => '101', 'nama' => 'IGD', 'departemen_id' => '18']];
        $this->getJson('/operasional/get-ruangan')->assertOk()
            ->assertExactJson(['success' => true, 'data' => $ruangan, 'ruangan' => $ruangan]);
    }

    public function test_perawat_tidak_bisa_mengambil_pasien_ruangan_lain(): void
    {
        $this->actingAs($this->perawat());
        foreach (['rawat-inap', 'rawat-jalan'] as $api) {
            foreach (['?ruanganfk=303', '?ruanganfk[]=101', ''] as $query) {
                $this->getJson('/operasional/' . $api . $query)->assertForbidden();
            }
        }
    }

    public function test_respons_pasien_perawat_tidak_membocorkan_data_mentah_ruangan_lain(): void
    {
        $request = Request::create('/operasional/rawat-inap');
        $user = $this->perawat();
        $request->setUserResolver(fn () => $user);
        $data = ['data' => [
            ['namaruangan' => 'IGD', 'nocm' => 'RM-UJI-1', 'namapasien' => 'Pasien Uji IGD', 'namalengkap' => 'Dokter Uji'],
            ['namaruangan' => 'SANDAT', 'nocm' => 'RM-UJI-2', 'namapasien' => 'Pasien Uji Sandat', 'namalengkap' => 'Dokter Uji'],
        ]];
        $method = new \ReflectionMethod(OperasionalCssdController::class, 'responsePasienRuangan');
        $response = $method->invoke(new OperasionalCssdController(), $request, $data, json_encode($data));

        $this->assertCount(1, $response->getData(true)['pasien']);
        $this->assertStringNotContainsString('RM-UJI-2', $response->getContent());
        $this->assertStringNotContainsString('Pasien Uji Sandat', $response->getContent());
    }

    public function test_perawat_dapat_menyimpan_kelayakan_ruangan_sendiri(): void
    {
        $layak = $this->buatDistribusi('IGD');
        $rusak = $this->buatDistribusi('IGD');
        $this->actingAs($this->perawat());
        $this->postJson('/input-perawat/simpan', $this->penilaian([$layak['log']]))
            ->assertOk()->assertJsonPath('jumlah', 1);
        $this->postJson('/input-perawat/simpan', $this->penilaian([$rusak['log']], ['hasil_uji_perawat' => 'TIDAK LAYAK']))
            ->assertOk();

        $this->assertDatabaseHas('cssd_keluar_logs', ['id' => $layak['log'], 'nama_section_pengguna' => 'IGD', 'hasil_uji_perawat' => 'LAYAK']);
        $this->assertDatabaseHas('cssd_ujis', ['cssd_keluar_log_id' => $layak['log'], 'hasil' => 'LAYAK']);
        $this->assertDatabaseHas('cssd_items', ['id' => $rusak['item'], 'status' => 'DISPOSE']);
    }

    public function test_id_barang_ruangan_lain_ditolak_dan_batch_dibatalkan_seluruhnya(): void
    {
        $sendiri = $this->buatDistribusi('IGD');
        $lain = $this->buatDistribusi('SANDAT');
        $this->actingAs($this->perawat());
        $this->postJson('/input-perawat/simpan', $this->penilaian([$lain['log']]))->assertForbidden();
        $this->postJson('/input-perawat/simpan', $this->penilaian([$sendiri['log'], $lain['log']], ['hasil_uji_perawat' => 'TIDAK LAYAK']))
            ->assertForbidden();

        $this->assertDatabaseHas('cssd_items', ['id' => $sendiri['item'], 'status' => 'KELUAR']);
        $this->assertDatabaseHas('cssd_keluar_logs', ['id' => $sendiri['log'], 'hasil_uji_perawat' => null]);
        $this->assertDatabaseHas('cssd_keluar_logs', ['id' => $lain['log'], 'hasil_uji_perawat' => null]);
        $this->assertDatabaseCount('cssd_ujis', 0);
        $this->assertDatabaseCount('cssd_logs', 0);
    }

    public function test_ruangan_kiriman_tidak_bisa_diubah_ke_ruangan_lain(): void
    {
        $item = $this->buatDistribusi('IGD');
        $this->actingAs($this->perawat())
            ->postJson('/input-perawat/simpan', $this->penilaian([$item['log']], ['nama_section_pengguna' => 'SANDAT']))
            ->assertForbidden();
        $this->assertDatabaseHas('cssd_keluar_logs', ['id' => $item['log'], 'nama_section_pengguna' => 'IGD', 'hasil_uji_perawat' => null]);
    }

    public function test_log_distribusi_lama_tidak_memberi_akses_ke_barang_yang_sudah_pindah_ruangan(): void
    {
        $item = $this->buatDistribusi('IGD');
        DB::table('cssd_items')->where('id', $item['item'])->update(['last_unit' => 'SANDAT']);
        $this->actingAs($this->perawat())->getJson('/operasional/keluar-data')->assertExactJson([]);
        $this->postJson('/input-perawat/simpan', $this->penilaian([$item['log']]))->assertForbidden();
    }

    public function test_super_admin_masih_bisa_menilai_barang_dari_ruangan_lain(): void
    {
        $item = $this->buatDistribusi('SANDAT');
        $this->actingAs(User::factory()->create(['role' => 'super_admin']))
            ->getJson('/operasional/keluar-data')->assertOk()->assertJsonCount(1);
        $this->postJson('/input-perawat/simpan', $this->penilaian([$item['log']], ['nama_section_pengguna' => 'SANDAT']))->assertOk();
    }

    public function test_admin_wajib_menetapkan_ruangan_perawat_dan_bisa_mengubahnya(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'super_admin']));
        $data = [
            'name' => 'Perawat Uji', 'email' => 'perawat-uji@example.test', 'role' => 'user_perawat',
            'password' => 'password-uji', 'password_confirmation' => 'password-uji',
        ];
        $this->postJson('/users/tambah', $data)->assertUnprocessable()->assertJsonValidationErrors(['ruangan_id', 'nama_ruangan']);
        $data += ['ruangan_id' => '101', 'nama_ruangan' => 'IGD', 'departemen_id' => '18'];
        $id = $this->postJson('/users/tambah', $data)->assertOk()->assertJsonPath('user.nama_ruangan', 'IGD')->json('user.id');

        $this->getJson('/users/get/' . $id)->assertOk()->assertJsonPath('user.ruangan_id', '101');
        $this->getJson('/users/data?draw=1&search[value]=IGD')->assertOk()->assertJsonCount(1, 'data');
        $this->putJson('/users/edit/' . $id, array_replace($data, ['ruangan_id' => '303', 'nama_ruangan' => 'SANDAT', 'departemen_id' => '16']))
            ->assertOk()->assertJsonPath('user.nama_ruangan', 'SANDAT');
        $this->putJson('/users/edit/' . $id, array_replace($data, ['role' => 'user_cssd']))
            ->assertOk()->assertJsonPath('user.ruangan_id', null)->assertJsonPath('user.nama_ruangan', null);

        unset($data['ruangan_id'], $data['nama_ruangan'], $data['departemen_id']);
        $this->postJson('/users/tambah', array_replace($data, ['role' => 'user_cssd', 'email' => 'cssd-uji@example.test']))->assertOk();
    }

    public function test_perawat_tidak_dapat_mengubah_ruangan_akun_atau_mengakses_endpoint_cssd(): void
    {
        $user = $this->perawat();
        $this->actingAs($user);
        $this->putJson('/users/edit/' . $user->id, ['ruangan_id' => '303', 'nama_ruangan' => 'SANDAT'])->assertForbidden();
        foreach (['/users/data', '/item-alat/data', '/operasional/item-data', '/operasional/item/1',
            '/operasional/item-kode/CPAP-01', '/operasional/distribusi-data', '/operasional/perawat-selesai-data',
            '/operasional/log-data', '/laporan-reuse/data', '/labeling', '/ready', '/dispose'] as $url) {
            $this->getJson($url)->assertForbidden();
        }
    }

    private function perawat(array $attributes = []): User
    {
        return User::factory()->create(array_replace([
            'role' => 'user_perawat', 'ruangan_id' => '101', 'nama_ruangan' => 'IGD', 'departemen_id' => '18',
        ], $attributes));
    }

    private function buatDistribusi(?string $ruangan, string $status = 'KELUAR'): array
    {
        $id = DB::table('cssd_items')->insertGetId([
            'bmhp_id' => $this->bmhpId, 'kode_unik' => 'TEST-' . (DB::table('cssd_items')->count() + 1),
            'status' => $status, 'last_unit' => $ruangan,
        ]);
        $log = DB::table('cssd_keluar_logs')->insertGetId([
            'cssd_item_id' => $id, 'tanggal_keluar' => '2026-09-22', 'jam_keluar' => '08:00',
            'tanggal_penggunaan' => '2026-09-22', 'nama_section_pengguna' => $ruangan ?? '',
            'no_rm' => '-', 'nama_dpjp' => '-', 'nama_perawat' => '-', 'petugas' => 'Petugas Uji', 'reuse_ke_keluar' => 1,
        ]);

        return ['item' => $id, 'log' => $log];
    }

    private function penilaian(array $ids, array $overrides = []): array
    {
        return array_replace([
            'cssd_keluar_log_ids' => $ids, 'tanggal_penggunaan' => '2026-09-22', 'jam_penggunaan' => '09:00',
            'nama_section_pengguna' => 'IGD', 'no_rm' => 'RM-UJI', 'nama_pasien' => 'Pasien Uji',
            'nama_dpjp' => 'Dokter Uji', 'nama_perawat' => 'Perawat Uji', 'hasil_uji_perawat' => 'LAYAK',
        ], $overrides);
    }

    private function buatSchema(): void
    {
        $this->migrasi('0001_01_01_000000_create_users_table.php');
        $this->migrasi('2026_05_13_000008_add_role_to_users_table.php');
        $this->migrasi('2026_04_28_000001_create_master_bmhp_table.php');

        // Dua tabel ini memakai ALTER ENUM khusus MySQL pada migrasi lama.
        // Schema pengujian berada di SQLite in-memory, terpisah dari database aplikasi.
        Schema::create('cssd_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bmhp_id')->constrained('master_bmhp');
            $table->string('kode_unik')->unique();
            $table->unsignedTinyInteger('reuse_ke')->default(0);
            $table->string('status')->default('READY');
            $table->string('last_unit')->nullable();
            $table->timestamps();
        });
        Schema::create('cssd_keluar_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cssd_item_id')->constrained('cssd_items');
            $table->date('tanggal_penggunaan');
            foreach (['nama_section_pengguna', 'no_rm', 'nama_dpjp', 'nama_perawat', 'petugas'] as $kolom) {
                $table->string($kolom);
            }
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });
        foreach ([
            '2026_04_29_000003_create_kriteria_rusak_table.php',
            '2026_04_29_000004_create_cssd_operasional_tables.php',
            '2026_04_30_000007_add_keluar_log_relation_to_operasional_tables.php',
            '2026_06_19_000010_add_perawat_flow_to_cssd_keluar_logs_table.php',
            '2026_07_28_000011_add_steril_expire_fields_to_cssd_tables.php',
            '2026_07_29_000012_add_nama_pasien_to_cssd_keluar_logs_table.php',
            '2026_08_10_000013_add_over_reuse_approval_to_cssd_keluar_logs_table.php',
            '2026_09_22_000014_add_ruangan_to_users_table.php',
        ] as $migration) {
            $this->migrasi($migration);
        }
    }

    private function migrasi(string $file): void
    {
        (require database_path('migrations/' . $file))->up();
    }
}
