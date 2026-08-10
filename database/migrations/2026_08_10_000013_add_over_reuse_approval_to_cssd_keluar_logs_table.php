<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cssd_keluar_logs', function (Blueprint $table) {
            $table->boolean('approval_over_reuse')->default(false)->after('reuse_ke_keluar');
            $table->string('approval_dpjp')->nullable()->after('approval_over_reuse');
            $table->text('approval_alasan')->nullable()->after('approval_dpjp');
            $table->text('approval_catatan')->nullable()->after('approval_alasan');
        });
    }

    public function down(): void
    {
        Schema::table('cssd_keluar_logs', function (Blueprint $table) {
            $table->dropColumn([
                'approval_over_reuse',
                'approval_dpjp',
                'approval_alasan',
                'approval_catatan',
            ]);
        });
    }
};
