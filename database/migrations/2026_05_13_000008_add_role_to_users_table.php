<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->default('user')->after('email');
            $table->string('pegawai_id')->nullable()->after('role');
            $table->boolean('is_active')->default(true)->after('pegawai_id');
        });

        $adminAda = DB::table('users')
            ->where('role', 'admin')
            ->exists();

        if ($adminAda) {
            return;
        }

        $userPertama = DB::table('users')
            ->orderBy('id')
            ->first();

        if ($userPertama) {
            DB::table('users')
                ->where('id', $userPertama->id)
                ->update([
                    'role' => 'admin',
                    'is_active' => true,
                    'updated_at' => now(),
                ]);

            return;
        }

        DB::table('users')->insert([
            'name' => 'Administrator CSSD',
            'email' => 'admin@cssd.local',
            'role' => 'admin',
            'is_active' => true,
            'password' => Hash::make('password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'pegawai_id', 'is_active']);
        });
    }
};
