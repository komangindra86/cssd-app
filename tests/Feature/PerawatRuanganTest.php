<?php

namespace Tests\Feature;

use App\Http\Controllers\OperasionalCssdController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\Support\CssdSchema;
use Tests\TestCase;

class PerawatRuanganTest extends TestCase
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
        foreach (['rawat-inap', 'rawat-jalan', 'igd-pasien'] as $api) {
            foreach (['?ruanganfk=303', '?ruanganfk[]=101', ''] as $query) {
                $this->getJson('/operasional/' . $api . $query)->assertForbidden();
            }
        }
    }

    public function test_respons_pasien_perawat_tidak_membocorkan_data_mentah_ruangan_lain(): void
    {
        $request = Request::create('/operasional/rawat-inap', 'GET', ['ruanganfk' => '101', 'tanggal' => '2026-09-22']);
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

        $this->assertDatabaseHas('cssd_keluar_logs', [
            'id' => $layak['log'], 'nama_section_pengguna' => 'IGD', 'hasil_uji_perawat' => 'LAYAK',
            'no_rm' => 'RM-UJI', 'nama_pasien' => 'Pasien Uji', 'nama_dpjp' => 'Dokter Uji',
        ]);
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

    public function test_form_pasien_hanya_pilihan_api_dan_nama_tidak_bisa_diketik(): void
    {
        $this->actingAs($this->perawat())->get('/input-perawat')->assertOk()
            ->assertSee('<select id="norm"', false)
            ->assertSee('id="namapasien" readonly', false)
            ->assertSee('id="namadpjp" readonly', false)
            ->assertDontSee('list="listnormrawatinap"', false)
            ->assertDontSee('id="namadpjp" list="listpegawai"', false);
    }

    public function test_simpan_menolak_identitas_pasien_manual_meski_ada_token_valid(): void
    {
        $item = $this->buatDistribusi('IGD');
        $this->actingAs($this->perawat());
        $data = $this->penilaian([$item['log']]);

        foreach (['no_rm', 'nama_pasien', 'nama_dpjp'] as $kolom) {
            $this->postJson('/input-perawat/simpan', array_replace($data, [$kolom => 'Input manual']))
                ->assertUnprocessable()->assertJsonValidationErrors($kolom);
        }

        unset($data['pasien_token']);
        $this->postJson('/input-perawat/simpan', $data)->assertUnprocessable()->assertJsonValidationErrors('pasien_token');
        $this->assertDatabaseHas('cssd_keluar_logs', ['id' => $item['log'], 'no_rm' => '-', 'hasil_uji_perawat' => null]);
        $this->assertDatabaseCount('cssd_ujis', 0);
    }

    public function test_pilihan_pasien_palsu_atau_berubah_ditolak(): void
    {
        $item = $this->buatDistribusi('IGD');
        $this->actingAs($this->perawat());
        $data = $this->penilaian([$item['log']]);
        foreach (['RM-UJI', substr($data['pasien_token'], 0, -10) . 'diubah'] as $token) {
            $this->postJson('/input-perawat/simpan', array_replace($data, ['pasien_token' => $token]))
                ->assertUnprocessable()->assertJsonValidationErrors('pasien_token');
        }
        $this->assertDatabaseCount('cssd_ujis', 0);
    }

    public function test_pilihan_pasien_akun_lain_ditolak(): void
    {
        $item = $this->buatDistribusi('IGD');
        $this->actingAs($this->perawat());
        $data = $this->penilaian([$item['log']]);
        $this->actingAs($this->perawat())->postJson('/input-perawat/simpan', $data)
            ->assertUnprocessable()->assertJsonValidationErrors('pasien_token');
        $this->assertDatabaseCount('cssd_ujis', 0);
    }

    public function test_pilihan_pasien_harus_sesuai_tanggal_dan_ruangan(): void
    {
        $item = $this->buatDistribusi('IGD');
        $this->actingAs(User::factory()->create(['role' => 'super_admin']));
        $data = $this->penilaian([$item['log']]);
        foreach ([['tanggal_penggunaan' => '2026-09-23'], ['ruanganfk' => '303'], ['nama_ruangan_pasien' => 'SANDAT']] as $perubahan) {
            $this->postJson('/input-perawat/simpan', array_replace($data, $perubahan))
                ->assertUnprocessable()->assertJsonValidationErrors('pasien_token');
        }
        $this->assertDatabaseCount('cssd_ujis', 0);
    }

    public function test_pilihan_pasien_kedaluwarsa_harus_diminta_ulang(): void
    {
        $item = $this->buatDistribusi('IGD');
        $this->actingAs($this->perawat());
        $data = $this->penilaian([$item['log']]);
        $this->travel(3)->hours();
        try {
            $this->postJson('/input-perawat/simpan', $data)->assertUnprocessable()->assertJsonValidationErrors('pasien_token');
            $this->assertDatabaseCount('cssd_ujis', 0);
        } finally {
            $this->travelBack();
        }
    }

    public function test_data_pasien_api_yang_tidak_lengkap_tidak_bisa_disimpan(): void
    {
        $item = $this->buatDistribusi('IGD');
        $this->actingAs($this->perawat());
        foreach (['namapasien', 'namalengkap'] as $kolom) {
            $data = $this->penilaian([$item['log']]);
            $data['pasien_token'] = $this->tokenPasien('IGD', [$kolom => '']);
            $this->postJson('/input-perawat/simpan', $data)->assertUnprocessable()->assertJsonValidationErrors('pasien_token');
        }
        $this->assertDatabaseCount('cssd_ujis', 0);
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

    public function test_akses_pasien_tambahan_tidak_membuka_ruangan_atau_barang_lain(): void
    {
        $peri = $this->buatDistribusi('PERINATOLOGI');
        $nicu = $this->buatDistribusi('NICU');
        $this->actingAs($this->perawatPeri());

        $this->getJson('/operasional/get-ruangan')->assertOk()->assertJsonCount(1, 'ruangan')
            ->assertJsonPath('ruangan.0.nama', 'PERINATOLOGI');
        $this->getJson('/operasional/get-ruangan?konteks=pasien')->assertOk()->assertJsonCount(2, 'ruangan')
            ->assertJsonPath('ruangan.1.nama', 'NICU');
        $this->getJson('/operasional/keluar-data')->assertOk()->assertJsonCount(1)
            ->assertJsonFragment(['cssd_keluar_log_id' => $peri['log']]);
        $this->postJson('/input-perawat/simpan', $this->penilaianLintasRuangan([$nicu['log']]))->assertForbidden();
        $this->postJson('/input-perawat/simpan', $this->penilaianLintasRuangan([$peri['log'], $nicu['log']]))->assertForbidden();
        $this->assertDatabaseCount('cssd_ujis', 0);
        $this->assertDatabaseHas('cssd_keluar_logs', ['id' => $peri['log'], 'nama_ruangan_pasien' => null]);

        config(['services.bali_mandara.token' => null]);
        foreach (['rawat-inap', 'rawat-jalan', 'igd-pasien'] as $api) {
            $this->getJson('/operasional/' . $api . '?ruanganfk=402&tanggal=2026-09-22')->assertUnprocessable();
            $this->getJson('/operasional/' . $api . '?ruanganfk=303&tanggal=2026-09-22')->assertForbidden();
        }
    }

    public function test_pasien_yang_dikembalikan_hanya_dari_ruangan_pasien_yang_dipilih(): void
    {
        $this->actingAs($this->perawatPeri());
        $request = Request::create('/operasional/rawat-inap', 'GET', ['ruanganfk' => '402', 'tanggal' => '2026-09-22']);
        $request->setUserResolver(fn () => auth()->user());
        $data = ['data' => array_map(fn ($ruangan) => [
            'namaruangan' => $ruangan, 'nocm' => 'RM-' . $ruangan,
            'namapasien' => 'Pasien Uji ' . $ruangan, 'namalengkap' => 'Dokter Uji',
        ], ['PERINATOLOGI', 'NICU', 'SANDAT'])];
        $method = new \ReflectionMethod(OperasionalCssdController::class, 'responsePasienRuangan');
        $response = $method->invoke(new OperasionalCssdController(), $request, $data, json_encode($data));
        $this->assertCount(1, $response->getData(true)['pasien']);
        $this->assertSame('RM-NICU', $response->getData(true)['pasien'][0]['no_rm']);
        $this->assertStringNotContainsString('RM-PERINATOLOGI', $response->getContent());
        $this->assertStringNotContainsString('RM-SANDAT', $response->getContent());
    }

    public function test_alat_peri_dipakai_pasien_nicu_tetap_peri_sampai_kembali_ready(): void
    {
        $alat = $this->buatDistribusi('PERINATOLOGI');
        DB::table('cssd_items')->where('id', $alat['item'])->update(['kode_unik' => 'PERI-001', 'reuse_ke' => 1]);
        DB::table('cssd_keluar_logs')->where('id', $alat['log'])->update(['reuse_ke_keluar' => 2]);
        $perawat = $this->perawatPeri();
        $this->actingAs($perawat)->postJson('/input-perawat/simpan', $this->penilaianLintasRuangan([$alat['log']]))->assertOk();
        $this->assertDatabaseHas('cssd_items', [
            'id' => $alat['item'], 'last_unit' => 'PERINATOLOGI', 'kode_unik' => 'PERI-001', 'reuse_ke' => 1, 'status' => 'KELUAR',
        ]);
        $this->assertDatabaseHas('cssd_keluar_logs', [
            'id' => $alat['log'], 'nama_section_pengguna' => 'PERINATOLOGI', 'nama_ruangan_pasien' => 'NICU',
            'ruangan_pasien_id' => '402', 'no_rm' => 'RM-UJI', 'nama_pasien' => 'Pasien Uji', 'nama_dpjp' => 'Dokter Uji',
            'penilai_user_id' => $perawat->id, 'penilai_nama' => $perawat->name,
            'reuse_ke_keluar' => 2, 'approval_over_reuse' => false,
        ]);
        $this->assertStringContainsString('Ruangan alat: PERINATOLOGI; ruangan pasien: NICU', DB::table('cssd_logs')->value('keterangan'));

        $this->actingAs(User::factory()->create(['role' => 'user_cssd']));
        $this->getJson('/operasional/perawat-selesai-data?draw=1&search[value]=NICU')->assertOk()
            ->assertJsonPath('recordsFiltered', 1)->assertJsonPath('data.0.nama_ruangan_pasien', 'NICU');
        $this->postJson('/barang-masuk/simpan', [
            'cssd_item_id' => $alat['item'], 'tanggal_masuk' => '2026-09-23', 'kondisi_awal' => 'Baik',
            'cssd_keluar_log_id' => $alat['log'],
            'metode_steril' => 'DTT', 'tanggal_steril' => '2026-09-23', 'masa_expire_bulan' => 6,
            'petugas_penerima_pencucian' => 'CSSD Uji', 'petugas_pengemasan' => 'CSSD Uji', 'petugas_sterilisasi' => 'CSSD Uji',
        ])->assertOk()->assertJsonPath('status', 'READY')->assertJsonPath('reuse_ke', 2);
        $this->assertDatabaseHas('cssd_items', [
            'id' => $alat['item'], 'last_unit' => 'PERINATOLOGI', 'kode_unik' => 'PERI-001', 'reuse_ke' => 2, 'status' => 'READY',
        ]);
        $this->assertDatabaseHas('cssd_masuk_logs', ['cssd_item_id' => $alat['item'], 'unit_asal' => 'PERINATOLOGI', 'nama_section_pengguna' => 'PERINATOLOGI']);
        $this->assertLaporanDuaRuangan('PERINATOLOGI', 'NICU');
    }

    public function test_tidak_layak_lintas_ruangan_masuk_dispose_tanpa_pindah_ruangan(): void
    {
        $alat = $this->buatDistribusi('PERINATOLOGI');
        $this->actingAs($this->perawatPeri());
        $this->postJson('/input-perawat/simpan', array_replace($this->penilaianLintasRuangan([$alat['log']]), [
            'hasil_uji_perawat' => 'TIDAK LAYAK', 'catatan' => 'Rusak saat digunakan',
        ]))->assertOk();
        $this->assertDatabaseHas('cssd_items', ['id' => $alat['item'], 'last_unit' => 'PERINATOLOGI', 'status' => 'DISPOSE', 'reuse_ke' => 0]);
        $this->actingAs(User::factory()->create(['role' => 'user_cssd']));
        $this->getJson('/laporan-reuse/alat-rusak?draw=1&search[value]=NICU&tanggal_awal=2026-09-22&tanggal_akhir=2026-09-22')
            ->assertOk()->assertJsonPath('recordsFiltered', 1)
            ->assertJsonPath('data.0.nama_pengguna', 'PERINATOLOGI')->assertJsonPath('data.0.nama_ruangan_pasien', 'NICU');
    }

    public function test_izin_pasien_dicabut_menolak_token_lama_dan_tidak_menyimpan_penilaian(): void
    {
        $alat = $this->buatDistribusi('PERINATOLOGI');
        $user = $this->perawatPeri();
        $this->actingAs($user);
        $data = $this->penilaianLintasRuangan([$alat['log']]);
        $user->update(['ruangan_pasien_tambahan' => []]);
        $this->actingAs($user->fresh())->postJson('/input-perawat/simpan', $data)->assertForbidden();
        $this->getJson('/operasional/rawat-inap?ruanganfk=402')->assertForbidden();
        $this->getJson('/operasional/get-ruangan?konteks=pasien')->assertJsonCount(1, 'ruangan');
        $this->assertDatabaseCount('cssd_ujis', 0);
        $this->assertDatabaseHas('cssd_keluar_logs', ['id' => $alat['log'], 'nama_ruangan_pasien' => null]);
    }

    public function test_token_pasien_tidak_bisa_dipakai_dengan_nama_atau_id_ruangan_yang_diubah(): void
    {
        $alat = $this->buatDistribusi('PERINATOLOGI');
        $this->actingAs($this->perawatPeri());
        $data = $this->penilaianLintasRuangan([$alat['log']]);
        foreach ([['nama_ruangan_pasien' => 'PERINATOLOGI'], ['ruanganfk' => '401']] as $perubahan) {
            $this->postJson('/input-perawat/simpan', array_replace($data, $perubahan))
                ->assertUnprocessable()->assertJsonValidationErrors('pasien_token');
        }
        $this->assertDatabaseCount('cssd_ujis', 0);
    }

    public function test_izin_tambahan_tidak_berlaku_jika_ruangan_akun_belum_diatur(): void
    {
        $user = $this->perawatPeri();
        $user->update(['ruangan_id' => null, 'nama_ruangan' => null]);
        $this->actingAs($user->fresh())->getJson('/operasional/get-ruangan?konteks=pasien')->assertJsonCount(0, 'ruangan');
        $this->getJson('/operasional/rawat-inap?ruanganfk=402')->assertForbidden();
    }

    public function test_admin_dapat_menyimpan_dan_mencabut_izin_pasien_tanpa_mengubah_ruangan_alat(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'super_admin']));
        $data = [
            'name' => 'Perawat Peri Uji', 'email' => 'peri@example.test', 'role' => 'user_perawat',
            'password' => 'password-uji', 'password_confirmation' => 'password-uji',
            'ruangan_id' => '401', 'nama_ruangan' => 'PERINATOLOGI', 'departemen_id' => '16',
            'ruangan_pasien_tambahan' => [['id' => '402', 'nama' => 'NICU', 'departemen_id' => '16']],
        ];
        $id = $this->postJson('/users/tambah', $data)->assertOk()
            ->assertJsonPath('user.ruangan_pasien_tambahan.0.nama', 'NICU')->json('user.id');
        $this->getJson('/users/get/' . $id)->assertOk()->assertJsonPath('user.ruangan_pasien_tambahan.0.id', '402');
        $this->getJson('/users/data?draw=1&search[value]=peri@example.test')->assertOk()
            ->assertJsonPath('data.0.ruangan_pasien_tambahan.0.id', '402');
        $this->putJson('/users/edit/' . $id, array_replace($data, ['ruangan_pasien_tambahan' => []]))
            ->assertOk()->assertJsonPath('user.ruangan_pasien_tambahan', [])->assertJsonPath('user.nama_ruangan', 'PERINATOLOGI');
        $this->putJson('/users/edit/' . $id, array_replace($data, ['role' => 'user_cssd']))
            ->assertOk()->assertJsonPath('user.ruangan_pasien_tambahan', null);

        foreach ([['id' => '402'], ['id' => '402', 'nama' => 'NICU'], ['id' => ['402'], 'nama' => 'NICU', 'departemen_id' => '16']] as $invalid) {
            $this->putJson('/users/edit/' . $id, array_replace($data, ['ruangan_pasien_tambahan' => [$invalid]]))->assertUnprocessable();
        }
        $this->putJson('/users/edit/' . $id, array_replace($data, ['ruangan_pasien_tambahan' => array_merge($data['ruangan_pasien_tambahan'], $data['ruangan_pasien_tambahan'])]))
            ->assertUnprocessable();
        $this->actingAs($this->perawatPeri())->putJson('/users/edit/' . $id, $data)->assertForbidden();
    }

    public function test_laporan_lama_tetap_memakai_ruangan_sebelum_pemisahan(): void
    {
        $alat = $this->buatDistribusi('PERINATOLOGI');
        DB::table('cssd_keluar_logs')->where('id', $alat['log'])->update(['no_rm' => 'RM-UJI', 'nama_pasien' => 'Pasien Lama']);
        $this->actingAs(User::factory()->create(['role' => 'user_cssd']));
        $this->assertLaporanDuaRuangan('PERINATOLOGI', 'PERINATOLOGI');
    }

    public function test_super_admin_tidak_menimpa_ruangan_distribusi_saat_menilai(): void
    {
        $alat = $this->buatDistribusi('PERINATOLOGI');
        $this->actingAs(User::factory()->create(['role' => 'super_admin']));
        $this->postJson('/input-perawat/simpan', $this->penilaian([$alat['log']]))
            ->assertUnprocessable()->assertJsonValidationErrors('cssd_keluar_log_ids');
        $this->assertDatabaseHas('cssd_keluar_logs', ['id' => $alat['log'], 'nama_section_pengguna' => 'PERINATOLOGI']);
    }

    public function test_migrasi_tidak_mengubah_kode_ruangan_dan_jumlah_reuse_data_lama(): void
    {
        $migration = require database_path('migrations/2026_10_20_000015_add_ruangan_pasien_to_reuse.php');
        $migration->down();
        $alat = $this->buatDistribusi('PERINATOLOGI');
        DB::table('cssd_items')->where('id', $alat['item'])->update(['kode_unik' => 'PERI-001', 'reuse_ke' => 2]);
        $migration->up();
        $this->assertDatabaseHas('cssd_items', ['id' => $alat['item'], 'kode_unik' => 'PERI-001', 'reuse_ke' => 2, 'last_unit' => 'PERINATOLOGI']);
        $this->assertDatabaseHas('cssd_keluar_logs', ['id' => $alat['log'], 'nama_section_pengguna' => 'PERINATOLOGI', 'nama_ruangan_pasien' => null]);
    }

    private function assertLaporanDuaRuangan(string $alat, string $pasien): void
    {
        foreach (['pemakaian-pasien', 'cari-rm'] as $laporan) {
            $this->getJson('/laporan-reuse/' . $laporan . '?draw=1&length=-1&no_rm=RM-UJI&tanggal_awal=2026-09-22&tanggal_akhir=2026-09-22&search[value]=' . $pasien)
                ->assertOk()->assertJsonPath('recordsFiltered', 1)
                ->assertJsonPath('data.0.nama_pengguna', $alat)->assertJsonPath('data.0.nama_ruangan_pasien', $pasien);
            $this->getJson('/laporan-reuse/' . $laporan . '?draw=1&no_rm=RM-UJI&tanggal_awal=2026-09-23&tanggal_akhir=2026-09-23')
                ->assertOk()->assertJsonPath('recordsFiltered', 0);
        }
    }

    private function perawatPeri(): User
    {
        return $this->perawat([
            'ruangan_id' => '401', 'nama_ruangan' => 'PERINATOLOGI', 'departemen_id' => '16',
            'ruangan_pasien_tambahan' => [['id' => '402', 'nama' => 'NICU', 'departemen_id' => '16']],
        ]);
    }

    private function penilaianLintasRuangan(array $ids): array
    {
        return $this->penilaian($ids, [
            'nama_section_pengguna' => 'PERINATOLOGI', 'ruanganfk' => '402', 'nama_ruangan_pasien' => 'NICU',
            'pasien_token' => $this->tokenPasien('NICU'),
        ]);
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
        $ruangan = auth()->user()->dibatasiRuangan()
            ? (auth()->user()->nama_ruangan ?? 'IGD')
            : ($overrides['nama_section_pengguna'] ?? 'IGD');

        return array_replace([
            'cssd_keluar_log_ids' => $ids, 'tanggal_penggunaan' => '2026-09-22', 'jam_penggunaan' => '09:00',
            'nama_section_pengguna' => 'IGD', 'ruanganfk' => $this->idRuangan($ruangan),
            'nama_ruangan_pasien' => $ruangan,
            'pasien_token' => $this->tokenPasien($ruangan),
            'nama_perawat' => 'Perawat Uji', 'hasil_uji_perawat' => 'LAYAK',
        ], $overrides);
    }

    private function tokenPasien(string $ruangan, array $overrides = []): string
    {
        $request = Request::create('/operasional/rawat-inap', 'GET', [
            'ruanganfk' => $this->idRuangan($ruangan), 'tanggal' => '2026-09-22',
        ]);
        $request->setUserResolver(fn () => auth()->user());
        $data = ['response' => ['data' => [array_replace([
            'namaruangan' => $ruangan, 'nocm' => 'RM-UJI', 'namapasien' => 'Pasien Uji', 'namalengkap' => 'Dokter Uji',
        ], $overrides)]]];
        $method = new \ReflectionMethod(OperasionalCssdController::class, 'responsePasienRuangan');
        $response = $method->invoke(new OperasionalCssdController(), $request, $data, json_encode($data));

        return $response->getData(true)['pasien'][0]['pasien_token'] ?? '';
    }

    private function idRuangan(string $nama): string
    {
        return match ($nama) {
            'SANDAT' => '303',
            'PERINATOLOGI' => '401',
            'NICU' => '402',
            default => '101',
        };
    }
}
