<?php
declare(strict_types = 1);

namespace DASPRiD\Helios\Identity;

interface IdentityLookupInterface
{
    public function lookup($subject) : LookupResult;
}
