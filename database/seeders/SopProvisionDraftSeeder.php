<?php

namespace Database\Seeders;

use App\Domain\Organization\WorkTeamCode;
use App\Domain\Sop\SopProvisionStatus;
use App\Models\SopProvisionRecord;
use Illuminate\Database\Seeder;

/**
 * Draf ketentuan SOP 2026 dari transkripsi tiga SOP (docs/proses-bisnis/analisis-referensi.md).
 * Idempoten: dilewati bila tim sudah punya ketentuan apa pun untuk 2026. Tidak pernah
 * mengaktifkan; Super Admin meninjau, melengkapi link SOP, lalu memberlakukan.
 */
final class SopProvisionDraftSeeder extends Seeder
{
    public function run(): void
    {
        $drafts = [
            [WorkTeamCode::Dta, '437/BLSDM.34/OT.02.02/07/2026', '2026-07-06', [1, 2, 2, 3],
                'Draf dari SOP Pengelolaan Kegiatan Talenta Digital tahap 6 (seleksi + BA, 1 hari), 7 (SK & ST, 1 hari), 8 (data peserta, 1 hari). Tinjau sebelum diberlakukan.'],
            [WorkTeamCode::Kdd, '438/BLSDM.32/OT.02.02/05/2026', '2026-09-24', [5, 6, 6, 9],
                'Draf dari SOP KeDaS tahap 6 (seleksi, 5 hari), 7 (SK peserta, 1 hari), 8 (olah data, 3 hari). Perlu konfirmasi: BAP (H+5) dan ST (H+6) tidak disebut sebagai output SOP KeDaS.'],
            [WorkTeamCode::Asn, '439/BLSDM.32/OT.02.02/05/2026', '2026-09-24', [4, 4, 4, 6],
                'Draf dari SOP Aparatur Digital tahap 7 (seleksi, 3 hari), 8 (BAP, SK, pengumuman, 1 hari), 9 (olah data, 2 hari). Perlu konfirmasi: ST (H+4) tidak disebut sebagai output SOP Aparatur.'],
        ];

        foreach ($drafts as [$team, $number, $effectiveDate, [$bap, $sk, $st, $ready], $note]) {
            if (SopProvisionRecord::query()->where('team_code', $team)->where('fiscal_year', 2026)->exists()) {
                continue;
            }

            SopProvisionRecord::query()->create([
                'team_code' => $team,
                'fiscal_year' => 2026,
                'version' => 1,
                'status' => SopProvisionStatus::Draf,
                'sop_number' => $number,
                'sop_effective_date' => $effectiveDate,
                'sop_url' => null,
                'effective_from' => $effectiveDate,
                'bap_days' => $bap,
                'sk_days' => $sk,
                'st_days' => $st,
                'ready_days' => $ready,
                'note' => $note,
            ]);
        }

        $this->command?->info('Draf ketentuan SOP 2026 tersedia. Lengkapi link SOP lalu berlakukan di menu Pengaturan.');
    }
}
