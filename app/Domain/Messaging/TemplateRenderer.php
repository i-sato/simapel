<?php

declare(strict_types=1);

namespace App\Domain\Messaging;

/**
 * Mengisi template dengan penggantian teks biasa untuk placeholder daftar putih.
 *
 * Tidak ada evaluasi apa pun: sintaks Blade/PHP seperti {{ $x }}, {!! !!}, @php,
 * atau <?php di dalam template tetap menjadi teks. Nilai placeholder juga tidak
 * dipindai ulang, sehingga isi data pelatihan tidak dapat menyisipkan placeholder baru.
 */
final class TemplateRenderer
{
    public static function render(string $template, MessageValues $values): string
    {
        $replacements = [];

        foreach (Placeholder::cases() as $placeholder) {
            $replacements[$placeholder->token()] = $values->for($placeholder);
        }

        // strtr() mengganti sekali jalan dan tidak memproses hasil penggantian lagi.
        return strtr($template, $replacements);
    }
}
