<?php

declare(strict_types=1);

namespace App\Application\Identity\Exceptions;

use App\Domain\Shared\BusinessRuleViolation;

final class InvalidUserAccountData extends BusinessRuleViolation {}
