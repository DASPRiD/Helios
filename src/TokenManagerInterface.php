<?php
declare(strict_types=1);

namespace DASPRiD\Helios;

use Lcobucci\JWT\Token;

interface TokenManagerInterface
{
    const END_AT_SESSION_CLAIM = 'endAtSession';
    const SUBJECT_CLAIM = 'subject';
    const ISSUED_AT_CLAIM = 'issuedAt';

    /**
     * @param mixed $subject
     */
    public function getSignedToken($subject, int $lifetime, bool $endAtSession) : Token;

    public function parseSignedToken(string $serializedToken) : Token;
}
