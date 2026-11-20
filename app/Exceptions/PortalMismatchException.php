<?php

namespace App\Exceptions;

use RuntimeException;

class PortalMismatchException extends RuntimeException
{
    public static function forRole(): self
    {
        return new self('A role can only be assigned to a user in the same portal.');
    }
}
