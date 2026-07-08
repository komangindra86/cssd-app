<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cssd_masuk_logs', function (Blueprint $table) {
            $table->string('petugas_penerima_pencucian')->nullable()->after('petugas');
            $table->string('petugas_pengemasan')->nullable()->after('petugas_penerima_pencucian');
            $table->string('petugas_sterilisasi')->nullable()->after('petugas_pengemasan');
        });
    }

    public function down(): void
    {
        Schema::table('cssd_masuk_logs', function (Blueprint $table) {
            $table->dropColumn([
                'petugas_penerima_pencucian',
                'petugas_pengemasan',
                'petugas_sterilisasi',
            ]);
        });
    }
};
