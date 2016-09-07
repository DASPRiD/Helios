<?php
declare(strict_types=1);

namespace DASPRiD\Helios\Exception;

use DomainException;
use Lcobucci\JWT\Token;
use Throwable;

final class InvalidTokenException extends DomainException implements ExceptionInterface
{
    public static function fromMalformedToken(string $serializedToken, Throwable $parseException)
    {
        return new self(sprintf('Token "%s" is malformed', $serializedToken), 0, $parseException);
    }

    public static function fromExpiredToken(Token $token) : self
    {
        return new self(sprintf('Token "%s" has expired', (string) $token));
    }

    public static function fromIllegalToken(Token $token) : self
    {
        return new self(sprintf('Token "%s" is not properly signed', (string) $token));
    }
}
