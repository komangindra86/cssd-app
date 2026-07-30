<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cssd_items', function (Blueprint $table) {
            $table->date('tanggal_steril_terakhir')->nullable()->after('last_unit');
            $table->date('tanggal_expire_steril')->nullable()->after('tanggal_steril_terakhir');
        });

        Schema::table('cssd_sterilisasi_logs', function (Blueprint $table) {
            $table->unsignedTinyInteger('masa_expire_bulan')->nullable()->after('tanggal_steril');
            $table->date('tanggal_expire_steril')->nullable()->after('masa_expire_bulan');
        });
    }

    public function down(): void
    {
        Schema::table('cssd_sterilisasi_logs', function (Blueprint $table) {
            $table->dropColumn(['masa_expire_bulan', 'tanggal_expire_steril']);
        });

        Schema::table('cssd_items', function (Blueprint $table) {
            $table->dropColumn(['tanggal_steril_terakhir', 'tanggal_expire_steril']);
        });
    }
};
