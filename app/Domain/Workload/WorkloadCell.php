<?php

declare(strict_types=1);

namespace App\Domain\Workload;

final readonly class WorkloadCell
{
    /**
     * @param  list<int>  $trainingIds  pelatihan yang membebani pegawai pada periode ini
     */
    public function __construct(public array $trainingIds, public bool $conflict) {}

    public function count(): int
    {
        return count($this->trainingIds);
    }

    /** Tingkat intensitas 0–3 untuk tampilan (3 = tiga pelatihan atau lebih). */
    public function intensity(): int
    {
        return min(3, $this->count());
    }
}
