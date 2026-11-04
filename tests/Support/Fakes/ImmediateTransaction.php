<?php

namespace Tests\Support\Fakes;

use App\Application\Shared\Contracts\Transaction;

final class ImmediateTransaction implements Transaction
{
    public int $runs = 0;

    public function run(callable $callback): mixed
    {
        $this->runs++;

        return $callback();
    }
}
