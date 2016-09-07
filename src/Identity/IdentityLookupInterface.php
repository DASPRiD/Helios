<?php
declare(strict_types=1);

namespace DASPRiD\Helios\Identity;

interface IdentityLookupInterface
{
    /**
     * @param mixed $subject
     */
    public function lookup($subject) : LookupResult;
}
