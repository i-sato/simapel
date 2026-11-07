<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mitra (data organisasi) dan wilayah kerja + jam penutupan pendaftaran pada pelatihan (K18).
 * Data pelatihan lama tidak dipetakan otomatis: wilayah dan mitra dilengkapi saat diubah.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partners', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('normalized_name', 150)->unique();
            $table->string('region', 20);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('trainings', function (Blueprint $table) {
            $table->string('region', 20)->nullable()->after('zonation');
            // Jam penutupan dalam WIB; default 23.59 sesuai keputusan pemilik proyek.
            $table->time('registration_closes_at')->default('23:59:00')->after('registration_end');
            $table->foreignId('partner_id')->nullable()->after('partner_location')->constrained('partners')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('trainings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('partner_id');
            $table->dropColumn(['region', 'registration_closes_at']);
        });

        Schema::dropIfExists('partners');
    }
};
