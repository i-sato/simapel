<?php

declare(strict_types=1);

namespace App\Infrastructure\Session;

use App\Application\Identity\Contracts\SessionTerminator;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Query\Builder;

/**
 * Mengakhiri sesi dengan menghapus baris di tabel sesi. Hanya berlaku untuk
 * SESSION_DRIVER=database (bawaan proyek); driver lain diabaikan.
 */
final readonly class DatabaseSessionTerminator implements SessionTerminator
{
    public function __construct(
        private DatabaseManager $database,
        private Config $config,
    ) {}

    public function terminateOtherSessions(int $userId, ?string $keepSessionId): int
    {
        $query = $this->sessionsOf($userId);

        if ($query === null) {
            return 0;
        }

        if ($keepSessionId !== null) {
            $query->where('id', '!=', $keepSessionId);
        }

        return $query->delete();
    }

    public function terminateAllSessions(int $userId): int
    {
        return $this->sessionsOf($userId)?->delete() ?? 0;
    }

    private function sessionsOf(int $userId): ?Builder
    {
        if ($this->config->get('session.driver') !== 'database') {
            return null;
        }

        $connection = $this->config->get('session.connection');

        return $this->database
            ->connection(is_string($connection) ? $connection : null)
            ->table((string) $this->config->get('session.table', 'sessions'))
            ->where('user_id', $userId);
    }
}
