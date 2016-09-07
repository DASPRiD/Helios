<?php
declare(strict_types=1);

namespace DASPRiD\Helios\Identity;

use Assert\Assertion;

final class LookupResult
{
    /**
     * @var mixed
     */
    private $identity;

    /**
     * @param mixed $identity
     */
    private function __construct($identity)
    {
        $this->identity = $identity;
    }

    public static function invalid() : self
    {
        return new self(null);
    }

    /**
     * @param mixed $identity
     */
    public static function fromIdentity($identity) : self
    {
        return new self($identity);
    }

    public function hasIdentity() : bool
    {
        return null !== $this->identity;
    }

    /**
     * @return mixed
     */
    public function getIdentity()
    {
        Assertion::notNull($this->identity);
        return $this->identity;
    }
}
