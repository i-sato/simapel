<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Sengaja tidak membuat akun maupun data contoh. Akun pertama dibuat dengan
 * `php artisan simapel:user:create` agar tidak ada kredensial bawaan.
 * Yang diisi hanya data referensi yang aman: draf ketentuan SOP 2026.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(SopProvisionDraftSeeder::class);
        $this->command?->info('Tidak ada akun bawaan. Buat akun dengan: php artisan simapel:user:create');
    }
}
