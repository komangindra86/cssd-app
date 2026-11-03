<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\PegawaiSimrs;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use Tests\TestCase;

class SimrsApiTest extends TestCase
{
    private const SECRET = 'rahasia-uji';

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.simrs_api' => [
            'url' => 'https://apisimrs.example.test', 'token' => 'client-uji', 'secret' => self::SECRET, 'timeout' => 5,
        ]]);
        config(['services.bali_mandara.token' => 'token-uji', 'services.bali_mandara.pegawai_url' => 'https://simrs.example.test/pegawai']);
        $this->actingAs(User::factory()->make(['id' => 1, 'role' => 'super_admin']));
    }

    private function fakePegawai(): void
    {
        Http::fake(['simrs.example.test/pegawai*' => Http::response(['metaData' => ['code' => 200], 'response' => [
            ['id' => 11, 'namalengkap' => 'dr. DPJP Satu ', 'nik_intern' => 'NIK-PEGAWAI-RAHASIA', 'bankrekeningnomor' => 'REK-RAHASIA'],
            ['id' => 12, 'namalengkap' => 'dr. DPJP Dua'],
        ]])]);
    }

    public function test_rawat_inap_memakai_ruangan_pada_tanggal_dengan_signature_hmac(): void
    {
        $this->fakePegawai();
        Http::fake(['apisimrs.example.test/*' => function (HttpRequest $request) {
            $uri = parse_url($request->url(), PHP_URL_PATH) . '?' . parse_url($request->url(), PHP_URL_QUERY);
            $harapan = hash_hmac('sha256', implode("\n", ['GET', $uri, $request->header('X-Timestamp')[0], hash('sha256', '')]), self::SECRET);

            if ($request->header('X-Token')[0] !== 'client-uji' || !hash_equals($harapan, $request->header('X-Signature')[0])) {
                return Http::response(['metadata' => ['code' => 401]], 401);
            }

            return Http::response(['success' => true, 'total' => 3, 'data' => [
                $this->riwayat('RM-LAMA', 'ISOLASI JEPUN', 'ISOLASI JEPUN', '2024-12-28 00:29:14', ['objectpegawaifk' => 999]),
                $this->riwayat('RM-PINDAH', 'ISOLASI JEPUN ', 'KASUARI', '2026-10-12 08:00:00', ['alamatrmh' => 'ALAMAT-RAHASIA']),
                $this->riwayat('RM-BARU', 'ISOLASI JEPUN', 'ISOLASI JEPUN', '2026-10-13 07:00:00', ['objectpegawaifk' => 12]),
            ]]);
        }]);

        $response = $this->getJson('/operasional/rawat-inap?ruanganfk=311&tanggal=2026-10-13')->assertOk()
            ->assertJsonCount(3, 'pasien')
            ->assertJsonPath('pasien.0.no_rm', 'RM-BARU')
            ->assertJsonPath('pasien.1.no_rm', 'RM-PINDAH')
            ->assertJsonPath('pasien.1.ruangan', 'ISOLASI JEPUN')
            ->assertJsonPath('pasien.1.ruangan_sekarang', 'KASUARI')
            ->assertJsonPath('pasien.1.masuk_ruangan', '2026-10-12')
            ->assertJsonPath('pasien.2.no_rm', 'RM-LAMA')
            ->assertJsonPath('pasien.0.nama_dpjp', 'dr. DPJP Dua')
            ->assertJsonPath('pasien.1.nama_dpjp', 'dr. DPJP Satu')
            ->assertJsonPath('pasien.2.nama_dpjp', '');

        $this->assertStringNotContainsString('ALAMAT-RAHASIA', $response->getContent());
        $this->assertStringNotContainsString('Petugas Input', $response->getContent());
        Http::assertSent(fn (HttpRequest $request) => $request->url()
            === 'https://apisimrs.example.test/api/service/getdatariwayatRI?tanggal=2026-10-13&ruanganfk=311&limit=500');
    }

    public function test_request_ganda_409_diulang_sekali_dengan_timestamp_baru(): void
    {
        Sleep::fake();
        $this->fakePegawai();
        Http::fakeSequence('apisimrs.example.test/*')
            ->push(['metadata' => ['code' => 409, 'message' => 'Request yang sama sudah pernah diproses.']], 409)
            ->push(['success' => true, 'data' => [$this->riwayat('RM-1', 'KASUARI', 'KASUARI', '2026-11-01 08:00:00')]]);

        $this->getJson('/operasional/rawat-inap?ruanganfk=307&tanggal=2026-11-03')->assertOk()->assertJsonCount(1, 'pasien');

        $this->assertCount(2, Http::recorded(fn ($request) => str_contains($request->url(), 'apisimrs')));
        Sleep::assertSleptTimes(1);
    }

    public function test_autentikasi_ditolak_memberi_pesan_jelas(): void
    {
        Http::fake(['apisimrs.example.test/*' => Http::response(['metadata' => ['code' => 401, 'message' => 'Request sudah kedaluwarsa.']], 401)]);

        $this->getJson('/operasional/rawat-inap?ruanganfk=307&tanggal=2026-11-03')
            ->assertStatus(502)
            ->assertJsonPath('message', 'Autentikasi API SIMRS ditolak. Periksa token, secret, dan jam server.');
    }

    public function test_daftar_pegawai_untuk_dpjp_di_cache_tanpa_data_pribadi(): void
    {
        $this->fakePegawai();

        $this->assertSame(['11' => 'dr. DPJP Satu', '12' => 'dr. DPJP Dua'], app(PegawaiSimrs::class)->namaById());
        app(PegawaiSimrs::class)->namaById();

        Http::assertSentCount(1);
        $this->assertStringNotContainsString('RAHASIA', serialize(Cache::get('simrs.pegawai')));
    }

    public function test_tanpa_kredensial_kembali_ke_api_rawat_inap_lama(): void
    {
        config(['services.simrs_api.secret' => null, 'services.bali_mandara.rawat_inap_url' => null]);
        Http::fake();

        $this->getJson('/operasional/rawat-inap?ruanganfk=307&tanggal=2026-11-03')
            ->assertStatus(422)->assertJsonPath('message', 'Konfigurasi API rawat inap belum lengkap di .env.');
        Http::assertNothingSent();
    }

    private function riwayat(string $rm, string $ruangan, string $sekarang, string $masuk, array $tambahan = []): array
    {
        return array_replace([
            'nocm' => $rm, 'namapasien' => 'Pasien ' . $rm, 'namalengkap' => 'Petugas Input', 'objectpegawaifk' => 11,
            'ruanganDiTanggalItu' => $ruangan, 'ruanganSekarang' => $sekarang, 'ruanganAsal' => 'IGD ',
            'tglmasuk' => $masuk, 'tglkeluar' => null, 'status_keluar' => 'BELUM KELUAR', 'norec_pd' => 'norec-' . $rm,
        ], $tambahan);
    }
}
