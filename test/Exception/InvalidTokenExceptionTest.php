<?php
declare(strict_types=1);

namespace DASPRiD\HeliosTest\Exception;

use DASPRiD\Helios\Exception\InvalidTokenException;
use DomainException;
use Lcobucci\JWT\Token;
use PHPUnit_Framework_TestCase as TestCase;

class InvalidTokenExceptionTest extends TestCase
{
    public function testFromMalformedToken()
    {
        $parseException = new DomainException();
        $exception = InvalidTokenException::fromMalformedToken('foo', $parseException);
        $this->assertSame('Token "foo" is malformed', $exception->getMessage());
        $this->assertSame($parseException, $exception->getPrevious());
    }

    public function testFromExpiredToken()
    {
        $exception = InvalidTokenException::fromExpiredToken(new Token());
        $this->assertSame('Token ".." has expired', $exception->getMessage());
    }

    public function testFromIllegalToken()
    {
        $exception = InvalidTokenException::fromIllegalToken(new Token());
        $this->assertSame('Token ".." is not properly signed', $exception->getMessage());
    }
}
