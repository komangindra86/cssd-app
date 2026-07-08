<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cssd_masuk_logs', function (Blueprint $table) {
            $table->foreignId('cssd_keluar_log_id')->nullable()->after('cssd_item_id')->constrained('cssd_keluar_logs')->cascadeOnUpdate()->nullOnDelete();
        });

        Schema::table('cssd_sterilisasi_logs', function (Blueprint $table) {
            $table->foreignId('cssd_keluar_log_id')->nullable()->after('cssd_item_id')->constrained('cssd_keluar_logs')->cascadeOnUpdate()->nullOnDelete();
        });

        Schema::table('cssd_ujis', function (Blueprint $table) {
            $table->foreignId('cssd_keluar_log_id')->nullable()->after('cssd_item_id')->constrained('cssd_keluar_logs')->cascadeOnUpdate()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('cssd_ujis', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cssd_keluar_log_id');
        });

        Schema::table('cssd_sterilisasi_logs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cssd_keluar_log_id');
        });

        Schema::table('cssd_masuk_logs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cssd_keluar_log_id');
        });
    }
};
