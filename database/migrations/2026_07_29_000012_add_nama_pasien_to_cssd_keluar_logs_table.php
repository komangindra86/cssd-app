<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cssd_keluar_logs', function (Blueprint $table) {
            $table->string('nama_pasien')->nullable()->after('no_rm');
        });
    }

    public function down(): void
    {
        Schema::table('cssd_keluar_logs', function (Blueprint $table) {
            $table->dropColumn('nama_pasien');
        });
    }
};
