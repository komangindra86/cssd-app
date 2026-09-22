<?php

namespace Tests\Feature;

use App\Http\Controllers\OperasionalCssdController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

class IgdPasienTest extends TestCase
{
    public function test_url_igd_mengikuti_ruangan_dan_tanggal_penggunaan(): void
    {
        config(['services.bali_mandara.igd_url' => 'https://example.test/dashboard/igd-pasien?ruanganid=&dari=2026-09-22&sampai=2026-09-22&search=&statuspanggil=&limit=50&offset=99']);
        $method = new \ReflectionMethod(OperasionalCssdController::class, 'urlIgd');
        $url = $method->invoke(new OperasionalCssdController(), '322', '2026-10-01');
        parse_str(parse_url($url, PHP_URL_QUERY), $query);

        $this->assertSame('/dashboard/igd-pasien', parse_url($url, PHP_URL_PATH));
        $this->assertSame([
            'ruanganid' => '322', 'dari' => '2026-10-01', 'sampai' => '2026-10-01',
            'search' => '', 'statuspanggil' => '', 'limit' => '100', 'offset' => '0',
        ], $query);
        $this->assertArrayNotHasKey('ruanganfk', $query);
    }

    public function test_response_igd_memetakan_rm_pasien_dpjp_dan_membatasi_ruangan(): void
    {
        $request = Request::create('/operasional/igd-pasien', 'GET', ['ruanganfk' => '322', 'tanggal' => '2026-09-22']);
        $request->setUserResolver(fn () => $this->perawatIgd());
        $data = ['metaData' => ['code' => 200], 'response' => ['total' => 3, 'data' => [
            ['namaruangan' => 'IGD ', 'nocm' => '00-001', 'namapasien' => 'Pasien Uji IGD', 'namalengkap' => 'dr. Uji IGD'],
            ['namaruangan' => 'RUANG VK - IGD', 'nocm' => '00-002', 'namapasien' => 'Pasien Uji VK', 'namalengkap' => 'dr. Uji VK'],
            ['namaruangan' => 'SANDAT', 'nocm' => '00-003', 'namapasien' => 'Pasien Uji Sandat', 'namalengkap' => 'dr. Uji Sandat'],
        ]]];
        $method = new \ReflectionMethod(OperasionalCssdController::class, 'responsePasienRuangan');
        $response = $method->invoke(new OperasionalCssdController(), $request, $data, json_encode($data));
        $pasien = $response->getData(true)['pasien'];

        $this->assertCount(1, $pasien);
        $this->assertSame('00-001', $pasien[0]['no_rm']);
        $this->assertSame('Pasien Uji IGD', $pasien[0]['nama_pasien']);
        $this->assertSame('dr. Uji IGD', $pasien[0]['nama_dpjp']);
        $pilihan = json_decode(Crypt::decryptString($pasien[0]['pasien_token']), true);
        $this->assertSame('1', $pilihan['user_id']);
        $this->assertSame('322', $pilihan['ruangan_id']);
        $this->assertSame('2026-09-22', $pilihan['tanggal']);
        $this->assertSame('dr. Uji IGD', $pilihan['pasien']['nama_dpjp']);
        $this->assertStringNotContainsString('Pasien Uji VK', $response->getContent());
        $this->assertStringNotContainsString('Pasien Uji Sandat', $response->getContent());
    }

    public function test_nama_ruangan_igd_dari_api_dirapikan(): void
    {
        $method = new \ReflectionMethod(OperasionalCssdController::class, 'normalisasiRuangan');
        $ruangan = $method->invoke(new OperasionalCssdController(), ['response' => [
            ['value' => 322, 'label' => 'IGD ', 'objectdepartemenfk' => 9],
            ['value' => 312, 'label' => 'RUANG TRANSIT IGD', 'objectdepartemenfk' => 16],
        ]]);

        $this->assertSame(['id' => 322, 'nama' => 'IGD', 'departemen_id' => 9], $ruangan[0]);
        $this->assertSame(16, $ruangan[1]['departemen_id']);
    }

    public function test_pasien_igd_harus_login_dan_akses_ruangan_lain_ditolak(): void
    {
        $this->getJson('/operasional/igd-pasien?ruanganfk=322&tanggal=2026-09-22')->assertUnauthorized();
        $this->actingAs($this->perawatIgd());
        $this->getJson('/operasional/igd-pasien?ruanganfk=323&tanggal=2026-09-22')->assertForbidden();
        $this->getJson('/operasional/igd-pasien?ruanganfk[]=322&tanggal=2026-09-22')->assertForbidden();
    }

    public function test_tanggal_igd_wajib_valid_sebelum_memanggil_api(): void
    {
        config(['services.bali_mandara.token' => null]);
        $this->actingAs($this->perawatIgd());
        foreach (['', '2026-02-30', '22-09-2026'] as $tanggal) {
            $this->getJson('/operasional/igd-pasien?ruanganfk=322&tanggal=' . $tanggal)
                ->assertUnprocessable()->assertJsonValidationErrors('tanggal');
        }
    }

    public function test_konfigurasi_igd_tidak_lengkap_memberi_pesan_yang_jelas(): void
    {
        config(['services.bali_mandara.token' => null]);
        $this->actingAs($this->perawatIgd())
            ->getJson('/operasional/igd-pasien?ruanganfk=322&tanggal=2026-09-22')
            ->assertUnprocessable()->assertJsonPath('message', 'Konfigurasi API IGD belum lengkap di .env.');
    }

    private function perawatIgd(): User
    {
        return (new User())->forceFill([
            'id' => 1, 'role' => 'user_perawat', 'is_active' => true,
            'ruangan_id' => '322', 'nama_ruangan' => 'IGD', 'departemen_id' => '9',
        ]);
    }
}
