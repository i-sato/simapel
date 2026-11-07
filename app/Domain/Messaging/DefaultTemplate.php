<?php

declare(strict_types=1);

namespace App\Domain\Messaging;

/**
 * Template default, disusun mengikuti contoh template VBL pada business story.
 * Teksnya berisi placeholder sehingga selalu memakai data pelatihan terkini saat
 * ditampilkan (info yang belum ada tampil "(menyusul)").
 */
final class DefaultTemplate
{
    public static function body(bool $withLearningPath): string
    {
        $lines = [
            'Selamat datang dan selamat bergabung peserta pelatihan {{nama_pelatihan}} Balai Pelatihan Sumber Daya Manusia (BLSDM) Komunikasi dan Digital Yogyakarta.',
            '',
            'Yuk simak tata caranya. Teman-teman perlu melakukan:',
            '1. Login ke akun Digitalent di sini: {{link_login_digitalent}}',
            '2. Jika belum pernah memiliki akun, daftar terlebih dahulu di sini: {{link_registrasi_digitalent}}',
            '3. Daftar dan selesaikan pelatihan sesuai jadwal berikut:',
            '- Link pelatihan: {{link_pendaftaran}}',
            '- Jadwal pendaftaran: {{tanggal_pendaftaran}}',
            '- Pendaftaran ditutup: {{batas_pendaftaran}}',
            '- Jadwal pelatihan: {{tanggal_pelatihan}}',
            '- Daftar peserta: {{link_daftar_peserta}}',
        ];

        if ($withLearningPath) {
            array_push(
                $lines,
                '',
                'Pelatihan ini merupakan bagian dari rangkaian berjenjang yang diselesaikan secara berurutan:',
                '{{learning_path}}',
            );
        }

        array_push(
            $lines,
            '',
            '📌 Panduan Pendaftaran DTS:',
            '- Link: {{link_panduan_dts}}',
            '',
            '📌 Panduan Pendaftaran Pelatihan:',
            '- Link: {{link_panduan_pelatihan}}',
            '',
            'NB:',
            '1. Seluruh pelaksanaan pelatihan tidak dipungut biaya apa pun alias gratis.',
            '',
            'Terima kasih',
        );

        return implode("\n", $lines);
    }
}
