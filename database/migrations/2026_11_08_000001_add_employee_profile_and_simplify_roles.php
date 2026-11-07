<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Model peran berlapis (ADR 0008):
 * - peran global hanya super_admin dan pegawai;
 * - admin_regional menjadi pegawai dengan kapabilitas Admin (can_be_admin) agar haknya tidak hilang;
 * - profil pegawai sesuai template: pangkat/golongan ruang dan jabatan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('pangkat_golongan', 100)->nullable()->after('email');
            $table->string('jabatan', 150)->nullable()->after('pangkat_golongan');
            $table->boolean('can_be_admin')->default(false)->after('role');
        });

        DB::table('users')->where('role', 'super_admin_regional')->update(['role' => 'super_admin']);
        DB::table('users')->where('role', 'admin_regional')->update(['role' => 'pegawai', 'can_be_admin' => true]);
    }

    public function down(): void
    {
        DB::table('users')->where('role', 'super_admin')->update(['role' => 'super_admin_regional']);
        DB::table('users')->where('role', 'pegawai')->where('can_be_admin', true)->update(['role' => 'admin_regional']);

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['pangkat_golongan', 'jabatan', 'can_be_admin']);
        });
    }
};
