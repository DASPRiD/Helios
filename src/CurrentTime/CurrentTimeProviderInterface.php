<?php
declare(strict_types=1);

namespace DASPRiD\Helios\CurrentTime;

use DateTimeImmutable;

interface CurrentTimeProviderInterface
{
    public function getCurrentTime() : DateTimeImmutable;
}
