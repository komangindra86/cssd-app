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
        Schema::create('cssd_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bmhp_id')->constrained('master_bmhp')->cascadeOnUpdate()->restrictOnDelete();
            $table->string('kode_unik', 100)->unique();
            $table->unsignedTinyInteger('reuse_ke')->default(0);
            $table->enum('status', ['DIRTY', 'READY', 'EXPIRED', 'DISPOSE'])->default('READY');
            $table->string('last_unit')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cssd_items');
    }
};
