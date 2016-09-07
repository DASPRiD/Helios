<?php
declare(strict_types=1);

namespace DASPRiD\HeliosTest\Exception;

use DASPRiD\Helios\Exception\CookieNotFoundException;
use PHPUnit_Framework_TestCase as TestCase;

class CookieNotFoundExceptionTest extends TestCase
{
    public function testFromNonExistentCookie()
    {
        $exception = CookieNotFoundException::fromNonExistentCookie('foo');
        $this->assertSame('Cookie with name "foo" was not found', $exception->getMessage());
    }
}
