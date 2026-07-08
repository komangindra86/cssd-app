<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cssd_masuk_logs', function (Blueprint $table) {
            $table->date('tanggal_penggunaan')->nullable()->after('unit_asal');
            $table->string('nama_section_pengguna')->nullable()->after('tanggal_penggunaan');
            $table->string('no_rm')->nullable()->after('nama_section_pengguna');
            $table->string('nama_dpjp')->nullable()->after('no_rm');
            $table->string('nama_perawat')->nullable()->after('nama_dpjp');
        });
    }

    public function down(): void
    {
        Schema::table('cssd_masuk_logs', function (Blueprint $table) {
            $table->dropColumn([
                'tanggal_penggunaan',
                'nama_section_pengguna',
                'no_rm',
                'nama_dpjp',
                'nama_perawat',
            ]);
        });
    }
};
