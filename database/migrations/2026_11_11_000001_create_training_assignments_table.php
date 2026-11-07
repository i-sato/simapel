<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Penugasan Admin/Panitia per pelatihan (K4, K8). Alasan force disimpan bila
 * penugasan dibuat walaupun beririsan dengan penugasan lain.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('training_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('training_id')->constrained('trainings')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->string('role', 16);
            $table->text('force_reason')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['training_id', 'user_id', 'role']);
            $table->index(['user_id', 'role']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_assignments');
    }
};
