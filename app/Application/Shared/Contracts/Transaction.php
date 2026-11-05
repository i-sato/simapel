<?php

declare(strict_types=1);

namespace App\Application\Shared\Contracts;

/**
 * Menjalankan beberapa perubahan data sebagai satu kesatuan (semua atau tidak sama sekali).
 */
interface Transaction
{
    /**
     * @template TResult
     *
     * @param  callable(): TResult  $callback
     * @return TResult
     */
    public function run(callable $callback): mixed;
}
