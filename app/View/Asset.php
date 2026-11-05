<?php

namespace App\View;

/**
 * URL aset statis dengan versi berbasis waktu modifikasi file, sehingga
 * cache browser diperbarui otomatis tanpa langkah build.
 */
final class Asset
{
    public static function url(string $path): string
    {
        $file = public_path($path);
        $version = is_file($file) ? (string) filemtime($file) : '0';

        return asset($path).'?v='.$version;
    }
}
