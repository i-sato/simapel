<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 64)->unique();
            $table->text('value')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('work_teams', function (Blueprint $table) {
            $table->string('registration_guide_url', 2048)->nullable()->after('year');
        });

        // Ketentuan SOP berversi per kode tim kerja per tahun anggaran (ADR 0010).
        // Dikunci per kode tim (bukan baris work_teams) agar resolver dapat memakai tahun sebelumnya.
        Schema::create('sop_provisions', function (Blueprint $table) {
            $table->id();
            $table->string('team_code', 10);
            $table->unsignedSmallInteger('fiscal_year');
            $table->unsignedSmallInteger('version');
            $table->string('status', 16);
            $table->string('sop_number', 100)->nullable();
            $table->date('sop_effective_date')->nullable();
            $table->string('sop_url', 2048)->nullable();
            $table->date('effective_from');
            $table->date('superseded_from')->nullable();
            $table->unsignedTinyInteger('bap_days');
            $table->unsignedTinyInteger('sk_days');
            $table->unsignedTinyInteger('st_days');
            $table->unsignedTinyInteger('ready_days');
            $table->text('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['team_code', 'fiscal_year', 'version']);
            $table->index(['team_code', 'status', 'effective_from']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sop_provisions');

        Schema::table('work_teams', function (Blueprint $table) {
            $table->dropColumn('registration_guide_url');
        });

        Schema::dropIfExists('settings');
    }
};
