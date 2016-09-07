<?php
declare(strict_types=1);

namespace DASPRiD\Helios\CurrentTime;

use DateTimeImmutable;

final class SystemTimeProvider implements CurrentTimeProviderInterface
{
    public function getCurrentTime() : DateTimeImmutable
    {
        return new DateTimeImmutable();
    }
}
