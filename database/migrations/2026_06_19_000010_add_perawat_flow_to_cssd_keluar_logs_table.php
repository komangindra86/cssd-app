<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cssd_keluar_logs', function (Blueprint $table) {
            $table->date('tanggal_keluar')->nullable()->after('cssd_item_id');
            $table->time('jam_keluar')->nullable()->after('tanggal_keluar');
            $table->time('jam_penggunaan')->nullable()->after('tanggal_penggunaan');
            $table->string('perawat_penerima')->nullable()->after('petugas');
            $table->date('tanggal_uji_perawat')->nullable()->after('perawat_penerima');
            $table->time('jam_uji_perawat')->nullable()->after('tanggal_uji_perawat');
            $table->string('hasil_uji_perawat', 20)->nullable()->after('jam_uji_perawat');
            $table->text('catatan_uji_perawat')->nullable()->after('hasil_uji_perawat');
            $table->unsignedTinyInteger('reuse_ke_keluar')->nullable()->after('catatan_uji_perawat');
        });

        DB::table('cssd_keluar_logs')
            ->whereNull('tanggal_keluar')
            ->update([
                'tanggal_keluar' => DB::raw('tanggal_penggunaan'),
            ]);

        DB::table('users')
            ->where('role', 'admin')
            ->update(['role' => 'super_admin']);

        DB::table('users')
            ->where('role', 'user')
            ->update(['role' => 'user_cssd']);
    }

    public function down(): void
    {
        DB::table('users')
            ->where('role', 'super_admin')
            ->update(['role' => 'admin']);

        DB::table('users')
            ->where('role', 'user_cssd')
            ->update(['role' => 'user']);

        Schema::table('cssd_keluar_logs', function (Blueprint $table) {
            $table->dropColumn([
                'tanggal_keluar',
                'jam_keluar',
                'jam_penggunaan',
                'perawat_penerima',
                'tanggal_uji_perawat',
                'jam_uji_perawat',
                'hasil_uji_perawat',
                'catatan_uji_perawat',
                'reuse_ke_keluar',
            ]);
        });
    }
};
