<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('kriteria_rusak', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bmhp_id')->nullable()->constrained('master_bmhp')->cascadeOnUpdate()->nullOnDelete();
            $table->string('nama');
            $table->text('keterangan')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kriteria_rusak');
    }
};
