<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pelatihan (Draft → Final) dan learning path (ADR 0009). Salinan ketentuan SOP
 * disimpan pada pelatihan saat finalisasi (ADR 0010).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('learning_paths', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_team_id')->constrained('work_teams')->restrictOnDelete();
            $table->string('name', 150);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('trainings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_team_id')->constrained('work_teams')->restrictOnDelete();
            $table->string('status', 16)->index();
            $table->string('name', 255);
            $table->string('registration_url', 2048)->nullable();
            $table->date('registration_start')->nullable();
            $table->date('registration_end')->nullable();
            $table->date('training_start')->nullable();
            $table->date('training_end')->nullable();
            $table->string('partner_location', 255)->nullable();
            $table->string('participant_list_url', 2048)->nullable();
            $table->string('method', 16)->nullable();
            $table->unsignedInteger('batch')->nullable();
            $table->string('syllabus_url', 2048)->nullable();
            $table->string('level', 16)->nullable();
            $table->string('certification', 16)->nullable();
            $table->boolean('coc_shows_score')->nullable();
            $table->string('execution', 16)->nullable();
            $table->string('participant_category', 16)->nullable();
            $table->string('zonation', 20)->nullable();
            $table->unsignedInteger('lesson_hours')->nullable();
            $table->unsignedInteger('participant_target')->nullable();
            $table->unsignedInteger('registration_quota')->nullable();
            $table->foreignId('learning_path_id')->nullable()->constrained('learning_paths')->restrictOnDelete();

            // Salinan ketentuan SOP saat finalisasi; kosong bila tim belum punya ketentuan.
            $table->foreignId('sop_provision_id')->nullable()->constrained('sop_provisions')->restrictOnDelete();
            $table->unsignedSmallInteger('sop_fiscal_year')->nullable();
            $table->unsignedSmallInteger('sop_version')->nullable();
            $table->string('sop_number', 100)->nullable();
            $table->string('sop_url', 2048)->nullable();
            $table->unsignedTinyInteger('sop_bap_days')->nullable();
            $table->unsignedTinyInteger('sop_sk_days')->nullable();
            $table->unsignedTinyInteger('sop_st_days')->nullable();
            $table->unsignedTinyInteger('sop_ready_days')->nullable();
            $table->boolean('sop_is_fallback')->nullable();

            $table->timestamp('finalized_at')->nullable();
            $table->foreignId('finalized_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            // Satu level satu pelatihan per learning path (juga menahan balapan antartransaksi).
            $table->unique(['learning_path_id', 'level']);
            $table->index(['training_start', 'training_end']);
            $table->index(['registration_start', 'registration_end']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trainings');
        Schema::dropIfExists('learning_paths');
    }
};
