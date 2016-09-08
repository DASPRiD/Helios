<?php
declare(strict_types=1);

namespace DASPRiD\HeliosTest\Identity;

use Assert\InvalidArgumentException;
use DASPRiD\Helios\Identity\LookupResult;
use PHPUnit_Framework_TestCase as TestCase;

class LookupResultTest extends TestCase
{
    public function testValidResult()
    {
        $result = LookupResult::fromIdentity('foo');

        $this->assertTrue($result->hasIdentity());
        $this->assertSame('foo', $result->getIdentity());
    }

    public function testInvalidResult()
    {
        $result = LookupResult::invalid();

        $this->assertFalse($result->hasIdentity());
        $this->expectException(InvalidArgumentException::class);
        $result->getIdentity();
    }
}
