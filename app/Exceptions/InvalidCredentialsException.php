<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

/**
 * Thrown when a login attempt fails, whatever the cause.
 *
 * The message is deliberately identical for an unknown email, a wrong password,
 * or both, so the response never reveals whether an account exists.
 */
final class InvalidCredentialsException extends Exception
{
    public function __construct(string $message = 'These credentials do not match our records.')
    {
        parent::__construct($message);
    }
}
