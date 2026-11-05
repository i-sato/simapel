<?php

declare(strict_types=1);

namespace App\Application\Identity\Contracts;

interface SessionTerminator
{
    /**
     * Mengakhiri semua sesi milik akun kecuali sesi yang sedang dipakai.
     *
     * @return int jumlah sesi yang diakhiri
     */
    public function terminateOtherSessions(int $userId, ?string $keepSessionId): int;

    /**
     * @return int jumlah sesi yang diakhiri
     */
    public function terminateAllSessions(int $userId): int;
}
