<?php

declare(strict_types=1);

namespace App\Domain\Messaging;

/**
 * Daftar putih placeholder template pesan WA. Hanya token persis {{nama}} dari daftar
 * ini yang diganti; isi template tidak pernah diproses sebagai Blade/PHP.
 */
enum Placeholder: string
{
    case NamaPelatihan = 'nama_pelatihan';
    case TimKerja = 'tim_kerja';
    case Level = 'level';
    case Metode = 'metode';
    case LinkPendaftaran = 'link_pendaftaran';
    case TanggalPendaftaran = 'tanggal_pendaftaran';
    case TanggalPelatihan = 'tanggal_pelatihan';
    case LinkDaftarPeserta = 'link_daftar_peserta';
    case LinkLoginDigitalent = 'link_login_digitalent';
    case LinkRegistrasiDigitalent = 'link_registrasi_digitalent';
    case LinkPanduanDts = 'link_panduan_dts';
    case LinkPanduanPelatihan = 'link_panduan_pelatihan';
    case LearningPath = 'learning_path';

    public function token(): string
    {
        return '{{'.$this->value.'}}';
    }

    public function description(): string
    {
        return match ($this) {
            self::NamaPelatihan => 'Nama/tema pelatihan',
            self::TimKerja => 'Nama tim kerja',
            self::Level => 'Level pelatihan (Dasar/Menengah/Mahir)',
            self::Metode => 'Metode (Luring/Daring/Hybrid)',
            self::LinkPendaftaran => 'Link pendaftaran/pelatihan',
            self::TanggalPendaftaran => 'Periode pendaftaran',
            self::TanggalPelatihan => 'Periode pelatihan',
            self::LinkDaftarPeserta => 'Link daftar peserta (diisi setelah pendaftaran ditutup)',
            self::LinkLoginDigitalent => 'Link login Digitalent (Pengaturan)',
            self::LinkRegistrasiDigitalent => 'Link registrasi Digitalent (Pengaturan)',
            self::LinkPanduanDts => 'Link panduan pendaftaran DTS (Pengaturan)',
            self::LinkPanduanPelatihan => 'Link panduan pendaftaran pelatihan tim kerja',
            self::LearningPath => 'Daftar level learning path beserta link dan jadwalnya',
        };
    }

    /**
     * Placeholder yang wajib ada pada template kustom (Additional Features V.1 nomor 2c).
     *
     * @return list<self>
     */
    public static function required(): array
    {
        return [self::LinkPendaftaran, self::TanggalPendaftaran, self::TanggalPelatihan, self::LinkPanduanPelatihan];
    }
}
