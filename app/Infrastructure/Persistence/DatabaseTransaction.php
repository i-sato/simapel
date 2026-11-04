<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Application\Shared\Contracts\Transaction;
use Illuminate\Database\ConnectionInterface;

final readonly class DatabaseTransaction implements Transaction
{
    public function __construct(private ConnectionInterface $connection) {}

    public function run(callable $callback): mixed
    {
        // Diulang hingga 3 kali bila terjadi deadlock.
        return $this->connection->transaction(fn (): mixed => $callback(), 3);
    }
}
