<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Template WA kustom per anggota per pelatihan (maks. 3, diatur di Domain).
 * Template default tidak disimpan; ia dibentuk dari data pelatihan saat ditampilkan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('message_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('training_id')->constrained('trainings')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            // utf8mb4 eksplisit agar emoji (📌, ⚠️, 🎉) tersimpan utuh.
            $table->text('body')->charset('utf8mb4')->collation('utf8mb4_unicode_ci');
            $table->timestamps();

            $table->index(['training_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_templates');
    }
};
