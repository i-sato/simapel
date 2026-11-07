<?php

declare(strict_types=1);

namespace App\Application\Training\Exceptions;

use RuntimeException;

/**
 * Pengguna tidak berhak atas aksi pelatihan ini. Presentation memetakannya menjadi 403.
 */
final class TrainingAccessDenied extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Anda tidak berhak melakukan aksi ini pada pelatihan tersebut.');
    }
}
