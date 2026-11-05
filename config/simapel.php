<?php

return [

    /*
    | Pembatasan percobaan login per kombinasi NIP + alamat IP.
    */
    'login' => [
        'max_attempts' => (int) env('SIMAPEL_LOGIN_MAX_ATTEMPTS', 5),
        'decay_seconds' => (int) env('SIMAPEL_LOGIN_DECAY_SECONDS', 300),
    ],

    /*
    | Kebijakan kata sandi. Batas maksimum 72 mengikuti batas byte bcrypt.
    */
    'password' => [
        'min_length' => 12,
        'max_length' => 72,
    ],

    /*
    | Reverse proxy tepercaya (dipisah koma). Kosong berarti tidak ada proxy.
    */
    'trusted_proxies' => env('TRUSTED_PROXIES'),

    /*
    | Akun pengujian E2E. Hanya dibaca E2eSeeder di lingkungan non-production.
    */
    'e2e' => [
        'nip' => env('E2E_NIP'),
        'password' => env('E2E_PASSWORD'),
    ],

];
