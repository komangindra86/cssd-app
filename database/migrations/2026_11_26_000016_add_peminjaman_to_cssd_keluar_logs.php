<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Peminjaman alat antar unit: ruangan alat (pemilik) tetap, unit peminjam yang menilai dan membawa ke CSSD.
     */
    public function up(): void
    {
        Schema::table('cssd_keluar_logs', function (Blueprint $table) {
            $table->string('ruangan_peminjam')->nullable()->after('nama_section_pengguna');
            $table->unsignedBigInteger('peminjam_user_id')->nullable()->after('ruangan_peminjam');
            $table->string('peminjam_nama')->nullable()->after('peminjam_user_id');
            $table->timestamp('dipinjam_pada')->nullable()->after('peminjam_nama');
        });
    }

    public function down(): void
    {
        Schema::table('cssd_keluar_logs', function (Blueprint $table) {
            $table->dropColumn(['ruangan_peminjam', 'peminjam_user_id', 'peminjam_nama', 'dipinjam_pada']);
        });
    }
};
