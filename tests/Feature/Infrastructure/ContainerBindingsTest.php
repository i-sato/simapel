<?php

namespace Tests\Feature\Infrastructure;

use Tests\TestCase;

/**
 * Setiap port (interface) di app/Application/** /Contracts wajib punya implementasi
 * terdaftar di container, agar use case dapat di-resolve.
 */
final class ContainerBindingsTest extends TestCase
{
    public function test_every_application_contract_is_bound(): void
    {
        $contracts = [];

        foreach (glob(app_path('Application/*/Contracts/*.php')) ?: [] as $file) {
            $relative = str_replace([app_path().'/', '.php', '/'], ['', '', '\\'], $file);
            $contracts[] = 'App\\'.$relative;
        }

        $this->assertNotSame([], $contracts);

        foreach ($contracts as $contract) {
            $this->assertTrue(interface_exists($contract), "{$contract} bukan interface.");
            $this->assertInstanceOf($contract, $this->app->make($contract), "{$contract} belum di-binding.");
        }
    }
}
