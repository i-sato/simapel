<?php

declare(strict_types=1);

namespace App\Domain\Audit;

use LogicException;

/**
 * Kesalahan pemrograman: data sensitif tidak boleh masuk ke audit log.
 */
final class SensitiveAuditContext extends LogicException
{
    public static function forKey(string $key): self
    {
        return new self(sprintf('Kunci konteks audit "%s" dilarang karena memuat data sensitif.', $key));
    }

    public static function nonScalar(string $key): self
    {
        return new self(sprintf('Nilai konteks audit "%s" harus skalar atau null.', $key));
    }
}
