<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\Support\CssdSchema;
use Tests\TestCase;

class ItemAlatStatusTest extends TestCase
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
            'nama' => 'CPAP Uji', 'max_reuse' => 5, 'metode_steril' => 'DTT',
        ]);
        $this->actingAs(User::factory()->create(['role' => 'user_cssd', 'name' => 'Admin CSSD']));
    }

    public function test_status_ruangan_dan_reuse_alat_keluar_tidak_bisa_diubah_manual(): void
    {
        $id = $this->buatItem('KELUAR', 'IGD', 2, true);

        foreach ([
            ['status' => 'DIRTY'],
            ['status' => 'READY'],
            ['last_unit' => 'SANDAT'],
            ['reuse_ke' => 0],
        ] as $perubahan) {
            $this->putJson("/item-alat/edit/{$id}", $this->form($perubahan))
                ->assertUnprocessable()->assertJsonValidationErrors('status');
        }

        $this->assertDatabaseHas('cssd_items', ['id' => $id, 'status' => 'KELUAR', 'last_unit' => 'IGD', 'reuse_ke' => 2]);
        $this->assertDatabaseCount('cssd_logs', 0);
    }

    public function test_form_mengunci_field_saat_alat_keluar(): void
    {
        $this->get('/item-alat')->assertOk()
            ->assertSee('<option value="KELUAR" hidden>KELUAR</option>', false)
            ->assertSee("kunciitemkeluar(data.item.status === 'KELUAR')", false);
    }

    public function test_data_lain_alat_keluar_tetap_bisa_diubah(): void
    {
        $id = $this->buatItem('KELUAR', 'IGD', 2, true);

        $this->putJson("/item-alat/edit/{$id}", $this->form(['kode_unik' => 'CPAP-BARU', 'last_unit' => ' IGD ']))
            ->assertOk();

        $this->assertDatabaseHas('cssd_items', ['id' => $id, 'kode_unik' => 'CPAP-BARU', 'status' => 'KELUAR']);
        $this->assertDatabaseCount('cssd_logs', 0);
    }

    public function test_status_keluar_tidak_bisa_diberikan_manual(): void
    {
        $id = $this->buatItem('READY', 'IGD', 2, true);

        $this->putJson("/item-alat/edit/{$id}", $this->form(['status' => 'KELUAR']))
            ->assertUnprocessable()->assertJsonValidationErrors('status');
        $this->postJson('/item-alat/tambah', $this->form(['kode_unik' => 'CPAP-2', 'status' => 'KELUAR']))
            ->assertUnprocessable()->assertJsonValidationErrors('status');

        $this->assertDatabaseHas('cssd_items', ['id' => $id, 'status' => 'READY']);
    }

    public function test_dirty_hanya_untuk_alat_yang_belum_pernah_didistribusikan(): void
    {
        $pernahKeluar = $this->buatItem('READY', 'IGD', 2, true);
        $baru = $this->buatItem('READY', null, 0, false);

        $this->putJson("/item-alat/edit/{$pernahKeluar}", $this->form(['status' => 'DIRTY']))
            ->assertUnprocessable()->assertJsonValidationErrors('status');
        $this->putJson("/item-alat/edit/{$baru}", $this->form(['kode_unik' => 'CPAP-BARU', 'status' => 'DIRTY', 'reuse_ke' => 0, 'last_unit' => null]))
            ->assertOk();

        $this->assertDatabaseHas('cssd_items', ['id' => $pernahKeluar, 'status' => 'READY']);
        $this->assertDatabaseHas('cssd_items', ['id' => $baru, 'status' => 'DIRTY']);
    }

    public function test_perubahan_status_manual_dicatat_di_log(): void
    {
        $id = $this->buatItem('READY', 'IGD', 2, true);

        $this->putJson("/item-alat/edit/{$id}", $this->form(['status' => 'DISPOSE']))->assertOk();

        $this->assertDatabaseHas('cssd_logs', [
            'cssd_item_id' => $id, 'status' => 'DISPOSE', 'petugas' => 'Admin CSSD',
            'keterangan' => 'Status diubah manual dari READY ke DISPOSE melalui menu Item Alat',
        ]);
    }

    private function buatItem(string $status, ?string $ruangan, int $reuse, bool $pernahKeluar): int
    {
        $id = DB::table('cssd_items')->insertGetId([
            'bmhp_id' => $this->bmhpId, 'kode_unik' => 'CPAP-' . (DB::table('cssd_items')->count() + 1),
            'status' => $status, 'last_unit' => $ruangan, 'reuse_ke' => $reuse,
        ]);

        if ($pernahKeluar) {
            DB::table('cssd_keluar_logs')->insert([
                'cssd_item_id' => $id, 'tanggal_keluar' => '2026-10-22', 'jam_keluar' => '08:00',
                'tanggal_penggunaan' => '2026-10-22', 'nama_section_pengguna' => $ruangan ?? 'IGD',
                'no_rm' => '-', 'nama_dpjp' => '-', 'nama_perawat' => '-', 'petugas' => 'Petugas Uji',
                'reuse_ke_keluar' => $reuse + 1,
            ]);
        }

        return $id;
    }

    private function form(array $overrides = []): array
    {
        return array_replace([
            'bmhp_id' => $this->bmhpId, 'kode_unik' => 'CPAP-1', 'reuse_ke' => 2,
            'status' => 'KELUAR', 'last_unit' => 'IGD',
        ], $overrides);
    }
}
