<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE cssd_items MODIFY status ENUM('DIRTY', 'READY', 'KELUAR', 'EXPIRED', 'DISPOSE') NOT NULL DEFAULT 'READY'");

        Schema::create('cssd_keluar_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cssd_item_id')->constrained('cssd_items')->cascadeOnUpdate()->restrictOnDelete();
            $table->date('tanggal_penggunaan');
            $table->string('nama_section_pengguna');
            $table->string('no_rm', 100);
            $table->string('nama_dpjp');
            $table->string('nama_perawat');
            $table->string('petugas');
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cssd_keluar_logs');
        DB::statement("ALTER TABLE cssd_items MODIFY status ENUM('DIRTY', 'READY', 'EXPIRED', 'DISPOSE') NOT NULL DEFAULT 'READY'");
    }
};
