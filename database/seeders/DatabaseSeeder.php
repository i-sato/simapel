<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Sengaja tidak membuat akun maupun data contoh. Akun pertama dibuat dengan
 * `php artisan simapel:user:create` agar tidak ada kredensial bawaan.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->command?->info('Tidak ada data bawaan. Buat akun dengan: php artisan simapel:user:create');
    }
}
