<?php
declare(strict_types=1);

namespace DASPRiD\Helios\Exception;

use OutOfBoundsException;

final class CookieNotFoundException extends OutOfBoundsException implements ExceptionInterface
{
    public static function fromNonExistentCookie($cookieName) : self
    {
        return new self(sprintf('Cookie with name "%s" was not found', $cookieName));
    }
}
