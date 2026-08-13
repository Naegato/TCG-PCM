<?php

declare(strict_types=1);

namespace App\Game\Exception;

use Throwable;

final class InvalidTargetException extends GameException
{
    public function __construct(string $reason, int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($reason, $code, $previous);
    }
}
