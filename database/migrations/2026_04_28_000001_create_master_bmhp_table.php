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
        Schema::create('master_bmhp', function (Blueprint $table) {
            $table->id();
            $table->string('nama')->unique();
            $table->unsignedTinyInteger('max_reuse');
            $table->enum('metode_steril', ['DTT', 'Plasma', 'Steam']);
            $table->text('kriteria_rusak')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('master_bmhp');
    }
};
