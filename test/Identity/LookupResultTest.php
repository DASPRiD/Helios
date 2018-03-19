<?php
declare(strict_types = 1);

namespace DASPRiD\HeliosTest\Identity;

use DASPRiD\Helios\Identity\LookupResult;
use PHPUnit\Framework\TestCase;

class LookupResultTest extends TestCase
{
    public function testValidResult() : void
    {
        $result = LookupResult::fromIdentity('foo');

        $this->assertTrue($result->hasIdentity());
        $this->assertSame('foo', $result->getIdentity());
    }

    public function testInvalidResult() : void
    {
        $result = LookupResult::invalid();

        $this->assertFalse($result->hasIdentity());
    }
}
