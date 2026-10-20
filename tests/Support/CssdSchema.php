<?php

namespace Tests\Support;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

trait CssdSchema
{
    private function buatSchema(): void
    {
        $this->migrasi('0001_01_01_000000_create_users_table.php');
        $this->migrasi('2026_05_13_000008_add_role_to_users_table.php');
        $this->migrasi('2026_04_28_000001_create_master_bmhp_table.php');

        // Migrasi lama memakai ALTER ENUM MySQL; tes menggunakan SQLite in-memory.
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
            '2026_04_29_000005_add_penggunaan_fields_to_cssd_masuk_logs_table.php',
            '2026_04_30_000007_add_keluar_log_relation_to_operasional_tables.php',
            '2026_06_19_000009_add_petugas_detail_to_cssd_masuk_logs_table.php',
            '2026_06_19_000010_add_perawat_flow_to_cssd_keluar_logs_table.php',
            '2026_07_28_000011_add_steril_expire_fields_to_cssd_tables.php',
            '2026_07_29_000012_add_nama_pasien_to_cssd_keluar_logs_table.php',
            '2026_08_10_000013_add_over_reuse_approval_to_cssd_keluar_logs_table.php',
            '2026_09_22_000014_add_ruangan_to_users_table.php',
            '2026_10_20_000015_add_ruangan_pasien_to_reuse.php',
        ] as $migration) {
            $this->migrasi($migration);
        }
    }

    private function migrasi(string $file): void
    {
        (require database_path('migrations/' . $file))->up();
    }
}
