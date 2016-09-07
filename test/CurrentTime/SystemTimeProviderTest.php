<?php
declare(strict_types=1);

namespace DASPRiD\HeliosTest\CurrentTime;

use DASPRiD\Helios\CurrentTime\SystemTimeProvider;
use DateTimeImmutable;
use PHPUnit_Framework_TestCase as TestCase;

class SystemTimeProviderTest extends TestCase
{
    public function testGetCurrentTime()
    {
        $timeProvider = new SystemTimeProvider();

        $dateTimeBefore = new DateTimeImmutable();
        $currentTime = $timeProvider->getCurrentTime();
        $dateTimeAfter = new DateTimeImmutable();

        $this->assertGreaterThanOrEqual($dateTimeBefore, $currentTime);
        $this->assertLessThanOrEqual($dateTimeAfter, $currentTime);
    }
}
