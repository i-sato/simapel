<?php

declare(strict_types=1);

namespace App\Application\Messaging\Contracts;

use App\Domain\Messaging\CustomTemplate;

interface CustomTemplateRepository
{
    /**
     * Template kustom milik satu anggota pada satu pelatihan, urut dari yang terlama.
     * Dengan $lock, baris dikunci sampai transaksi selesai.
     *
     * @return list<CustomTemplate>
     */
    public function forOwner(int $trainingId, int $ownerId, bool $lock = false): array;

    public function insert(CustomTemplate $template): int;

    public function update(CustomTemplate $template): void;

    /**
     * @param  list<int>  $ids
     */
    public function delete(array $ids): void;
}
