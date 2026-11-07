<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Dokumen administrasi BAP/SK/ST per pelatihan Final (K3, langkah 6). Berkas tetap di
 * penyimpanan kantor; hanya status, nomor, tanggal, link, dan penanggung jawab dicatat.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('administrative_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('training_id')->constrained('trainings')->cascadeOnDelete();
            $table->string('type', 8);
            $table->string('status', 16)->default('belum');
            $table->string('number', 100)->nullable();
            $table->date('document_date')->nullable();
            $table->string('file_url', 2048)->nullable();
            $table->foreignId('assignee_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['training_id', 'type']);
            $table->index(['assignee_id', 'status']);
        });

        // Pelatihan yang sudah Final sebelum langkah 6 mendapat dokumen "Belum" (hanya menambah baris).
        $now = now();

        foreach (DB::table('trainings')->where('status', 'final')->pluck('id') as $trainingId) {
            foreach (['bap', 'sk', 'st'] as $type) {
                DB::table('administrative_documents')->insertOrIgnore([
                    'training_id' => $trainingId,
                    'type' => $type,
                    'status' => 'belum',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('administrative_documents');
    }
};
