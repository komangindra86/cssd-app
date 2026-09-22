<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('ruangan_id', 100)->nullable();
            $table->string('nama_ruangan')->nullable();
            $table->string('departemen_id', 100)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['ruangan_id', 'nama_ruangan', 'departemen_id']);
        });
    }
};
