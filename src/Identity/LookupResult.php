<?php
declare(strict_types = 1);

namespace DASPRiD\Helios\Identity;

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

    public static function fromIdentity($identity) : self
    {
        return new self($identity);
    }

    public function hasIdentity() : bool
    {
        return null !== $this->identity;
    }

    public function getIdentity()
    {
        assert(null !== $this->identity);
        return $this->identity;
    }
}
