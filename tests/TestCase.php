<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    /**
     * Penjaga dijalankan sebelum trait (mis. RefreshDatabase) menyentuh database,
     * sehingga pengujian tidak pernah mengosongkan database pengembangan/produksi.
     *
     * @return array<class-string, class-string>
     */
    protected function setUpTraits()
    {
        $connection = (string) config('database.default');
        $database = (string) config("database.connections.{$connection}.database");

        if ($database !== ':memory:' && ! str_ends_with($database, '_testing')) {
            throw new RuntimeException(sprintf(
                'Pengujian ditolak: database "%s" bukan database pengujian (harus berakhiran _testing).',
                $database,
            ));
        }

        return parent::setUpTraits();
    }
}
