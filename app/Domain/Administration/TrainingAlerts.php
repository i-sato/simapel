<?php

declare(strict_types=1);

namespace App\Domain\Administration;

use DateTimeImmutable;

/**
 * Peringatan per pelatihan (langkah 6). Semua perhitungan per tanggal (inklusif) dan
 * memakai "hari ini" dari port Clock yang diteruskan pemanggil.
 *
 * - Dokumen BAP/SK/ST (pelatihan Final): tenggat = akhir pendaftaran + H+n dari salinan
 *   ketentuan pada pelatihan. Hari tenggat = "mendekati tenggat"; sesudahnya = "lewat tenggat".
 * - Kritis: dokumen belum lengkap setelah tenggat "administrasi siap" atau saat pelatihan
 *   sudah dimulai.
 * - Tenggat belum ditetapkan: pelatihan Final tanpa salinan ketentuan.
 * - Link daftar peserta kosong setelah pendaftaran ditutup.
 * - Segera berakhir: sedang berlangsung dan akhir pelatihan ≤ N hari lagi.
 * - Bentrok penugasan.
 */
final class TrainingAlerts
{
    /**
     * @return list<TrainingAlert> urut dari yang paling berat
     */
    public static function for(TrainingAlertInput $input, DateTimeImmutable $now): array
    {
        $today = $now->setTime(0, 0);
        $schedule = $input->schedule;
        $alerts = [];

        if ($input->isFinal) {
            $alerts = [...$alerts, ...self::documents($input, $today)];
        }

        if ($schedule->registrationEndedOn($today) && ! $input->hasParticipantList) {
            $alerts[] = new TrainingAlert(AlertKind::ParticipantListMissing, 'Link peserta kosong', 'Pendaftaran sudah ditutup, tetapi link daftar peserta belum diisi.');
        }

        if ($schedule->trainingStart !== null && $schedule->trainingEnd !== null
            && $today >= $schedule->trainingStart && $today <= $schedule->trainingEnd) {
            $daysLeft = (int) $today->diff($schedule->trainingEnd)->days;

            if ($daysLeft <= $input->closingSoonDays) {
                $alerts[] = new TrainingAlert(AlertKind::ClosingSoon, 'Segera berakhir', $daysLeft === 0 ? 'Pelatihan berakhir hari ini.' : sprintf('Pelatihan berakhir %d hari lagi.', $daysLeft));
            }
        }

        if ($input->conflictCount > 0) {
            $alerts[] = new TrainingAlert(AlertKind::AssignmentConflict, 'Bentrok penugasan', sprintf('%d pegawai yang ditugaskan memiliki penugasan lain yang beririsan.', $input->conflictCount));
        }

        $order = [AlertSeverity::Critical->value => 0, AlertSeverity::Warning->value => 1, AlertSeverity::Notice->value => 2];
        usort($alerts, fn (TrainingAlert $a, TrainingAlert $b): int => $order[$a->severity()->value] <=> $order[$b->severity()->value]);

        return $alerts;
    }

    /**
     * @return list<TrainingAlert>
     */
    private static function documents(TrainingAlertInput $input, DateTimeImmutable $today): array
    {
        $registrationEnd = $input->schedule->registrationEnd;

        if ($input->deadlines === null || $registrationEnd === null) {
            return [new TrainingAlert(AlertKind::DeadlineNotSet, 'Tenggat belum ditetapkan', 'Pelatihan tidak memiliki salinan ketentuan SOP, sehingga tenggat BAP/SK/ST tidak dapat dihitung.')];
        }

        $alerts = [];
        $pending = [];

        foreach ($input->documents as $document) {
            if ($document->isDone()) {
                continue;
            }

            $pending[] = $document->type->label();
            $deadline = DocumentDeadline::for($document->type, $registrationEnd, $input->deadlines);

            if ($today == $deadline) {
                $alerts[] = new TrainingAlert(AlertKind::DocumentDueToday, $document->type->label().' tenggat hari ini', sprintf('Tenggat %s jatuh hari ini (%s).', $document->type->fullLabel(), $deadline->format('d-m-Y')), $document->type);
            } elseif ($today > $deadline) {
                $alerts[] = new TrainingAlert(AlertKind::DocumentOverdue, $document->type->label().' lewat tenggat', sprintf('%s melewati tenggat %s.', $document->type->fullLabel(), $deadline->format('d-m-Y')), $document->type);
            }
        }

        $ready = DocumentDeadline::ready($registrationEnd, $input->deadlines);
        $started = $input->schedule->trainingStart !== null && $today >= $input->schedule->trainingStart;

        if ($pending !== [] && ($today > $ready || $started)) {
            array_unshift($alerts, new TrainingAlert(
                AlertKind::AdministrationCritical,
                'Administrasi kritis',
                sprintf('%s belum selesai %s.', implode(', ', $pending), $started ? 'padahal pelatihan sudah dimulai' : sprintf('melewati tenggat administrasi siap %s', $ready->format('d-m-Y'))),
            ));
        }

        return $alerts;
    }
}
