<?php

namespace App\Http\Support;

use App\Application\Identity\EmployeeImportRow;
use App\Application\Identity\ImportEmployees;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

/**
 * Membaca CSV impor pegawai (template daftar pegawai) menjadi baris mentah.
 * Validasi isi tiap baris dilakukan use case ImportEmployees.
 */
final class EmployeeCsvReader
{
    public const HEADER = ['nama', 'nip', 'pangkat_golongan', 'jabatan', 'email'];

    /**
     * @return list<EmployeeImportRow>
     *
     * @throws ValidationException bila struktur berkas tidak sesuai
     */
    public function read(UploadedFile $file): array
    {
        $handle = fopen($file->getRealPath(), 'r');

        if ($handle === false) {
            throw ValidationException::withMessages(['file' => 'Berkas tidak dapat dibaca.']);
        }

        try {
            $firstLine = (string) fgets($handle);
            $delimiter = substr_count($firstLine, ';') > substr_count($firstLine, ',') ? ';' : ',';
            $header = array_map(
                fn (?string $cell): string => mb_strtolower(trim((string) $cell)),
                str_getcsv(preg_replace('/^\xEF\xBB\xBF/', '', $firstLine) ?? '', $delimiter, '"', ''),
            );

            if ($header !== self::HEADER) {
                throw ValidationException::withMessages([
                    'file' => 'Header wajib persis: '.implode(',', self::HEADER).'.',
                ]);
            }

            $rows = [];
            $line = 1;

            while (($cells = fgetcsv($handle, null, $delimiter, '"', '')) !== false) {
                $line++;

                if ($cells === [null] || implode('', array_map(fn ($cell) => trim((string) $cell), $cells)) === '') {
                    continue;
                }

                if (count($rows) >= ImportEmployees::MAX_ROWS) {
                    throw ValidationException::withMessages([
                        'file' => sprintf('Maksimal %d baris data per impor.', ImportEmployees::MAX_ROWS),
                    ]);
                }

                if (count($cells) !== count(self::HEADER)) {
                    throw ValidationException::withMessages([
                        'file' => sprintf('Baris %d harus berisi tepat %d kolom.', $line, count(self::HEADER)),
                    ]);
                }

                [$name, $nip, $pangkat, $jabatan, $email] = array_map(fn ($cell): string => trim((string) $cell), $cells);
                $rows[] = new EmployeeImportRow($line, $name, $nip, $pangkat, $jabatan, $email);
            }

            return $rows;
        } finally {
            fclose($handle);
        }
    }
}
