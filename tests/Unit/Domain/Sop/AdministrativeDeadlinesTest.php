<?php

namespace Tests\Unit\Domain\Sop;

use App\Domain\Sop\AdministrativeDeadlines;
use App\Domain\Sop\InvalidSopProvision;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class AdministrativeDeadlinesTest extends TestCase
{
    public function test_accepts_sop_based_values(): void
    {
        $deadlines = AdministrativeDeadlines::of(5, 6, 6, 9);

        $this->assertSame([5, 6, 6, 9], [$deadlines->bapDays, $deadlines->skDays, $deadlines->stDays, $deadlines->readyDays]);
    }

    public function test_all_documents_on_the_same_day_is_allowed(): void
    {
        $deadlines = AdministrativeDeadlines::of(1, 1, 1, 1);

        $this->assertSame(1, $deadlines->readyDays);
        $this->assertSame(0, AdministrativeDeadlines::of(0, 0, 0, 0)->readyDays);
    }

    /**
     * @return iterable<string, array{int, int, int, int, string}>
     */
    public static function invalid(): iterable
    {
        yield 'negatif' => [-1, 1, 1, 1, 'bap_days'];
        yield 'melebihi 60' => [1, 61, 1, 61, 'sk_days'];
        yield 'siap lebih awal dari SK' => [1, 4, 1, 3, 'ready_days'];
        yield 'siap lebih awal dari ST' => [1, 1, 5, 4, 'ready_days'];
    }

    #[DataProvider('invalid')]
    public function test_rejects_invalid_values(int $bap, int $sk, int $st, int $ready, string $field): void
    {
        try {
            AdministrativeDeadlines::of($bap, $sk, $st, $ready);
            $this->fail('InvalidSopProvision seharusnya dilempar.');
        } catch (InvalidSopProvision $exception) {
            $this->assertSame($field, $exception->field());
        }
    }
}
