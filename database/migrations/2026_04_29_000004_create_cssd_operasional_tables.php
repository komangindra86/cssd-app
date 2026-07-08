<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cssd_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cssd_item_id')->constrained('cssd_items')->cascadeOnUpdate()->restrictOnDelete();
            $table->string('status', 50);
            $table->text('keterangan')->nullable();
            $table->date('tanggal');
            $table->string('petugas');
            $table->timestamps();
        });

        Schema::create('cssd_masuk_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cssd_item_id')->constrained('cssd_items')->cascadeOnUpdate()->restrictOnDelete();
            $table->string('unit_asal');
            $table->date('tanggal_masuk');
            $table->text('kondisi_awal')->nullable();
            $table->string('petugas');
            $table->timestamps();
        });

        Schema::create('cssd_sterilisasi_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cssd_item_id')->constrained('cssd_items')->cascadeOnUpdate()->restrictOnDelete();
            $table->enum('metode_steril', ['DTT', 'Plasma', 'Steam']);
            $table->date('tanggal_steril');
            $table->string('petugas');
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });

        Schema::create('cssd_ujis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cssd_item_id')->constrained('cssd_items')->cascadeOnUpdate()->restrictOnDelete();
            $table->boolean('visual_ok');
            $table->boolean('fungsi_ok');
            $table->json('kriteria_rusak')->nullable();
            $table->text('catatan')->nullable();
            $table->enum('hasil', ['LAYAK', 'TIDAK LAYAK']);
            $table->unsignedTinyInteger('reuse_ke');
            $table->date('tanggal_uji');
            $table->string('petugas');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cssd_ujis');
        Schema::dropIfExists('cssd_sterilisasi_logs');
        Schema::dropIfExists('cssd_masuk_logs');
        Schema::dropIfExists('cssd_logs');
    }
};
