<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->json('ruangan_pasien_tambahan')->nullable();
        });

        Schema::table('cssd_keluar_logs', function (Blueprint $table) {
            $table->string('ruangan_pasien_id', 100)->nullable();
            $table->string('nama_ruangan_pasien')->nullable();
            // Identitas pencatat tetap tersimpan walaupun akun kemudian dihapus.
            $table->unsignedBigInteger('penilai_user_id')->nullable();
            $table->string('penilai_nama')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('cssd_keluar_logs', function (Blueprint $table) {
            $table->dropColumn(['ruangan_pasien_id', 'nama_ruangan_pasien', 'penilai_user_id', 'penilai_nama']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('ruangan_pasien_tambahan');
        });
    }
};
