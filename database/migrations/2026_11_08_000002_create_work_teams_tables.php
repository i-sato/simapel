<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_teams', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10);
            $table->string('name', 150);
            $table->unsignedSmallInteger('year');
            $table->timestamps();

            $table->unique(['code', 'year']);
            $table->index('year');
        });

        Schema::create('team_memberships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_team_id')->constrained('work_teams')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->string('role', 20);
            $table->timestamps();

            // Satu pegawai satu peran per tim; aturan Ketua/Sekretaris tunggal ditegakkan Domain + lock tim.
            $table->unique(['work_team_id', 'user_id']);
            $table->index('user_id');
        });

        // Tiga tim kerja 2026 (problem statement). Tanpa data pegawai.
        $now = now();
        DB::table('work_teams')->insertOrIgnore([
            ['code' => 'KDD', 'name' => 'Keterampilan Digital Dasar', 'year' => 2026, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'ASN', 'name' => 'ASN Digital', 'year' => 2026, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'DTA', 'name' => 'Talenta Digital', 'year' => 2026, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('team_memberships');
        Schema::dropIfExists('work_teams');
    }
};
