<?php
declare(strict_types=1);

namespace DASPRiD\Helios;

use DASPRiD\Helios\CurrentTime\CurrentTimeProviderInterface;
use DASPRiD\Helios\CurrentTime\SystemTimeProvider;
use DASPRiD\Helios\Exception\InvalidTokenException;
use Exception;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Signer;
use Lcobucci\JWT\Token;
use Lcobucci\JWT\ValidationData;

final class TokenManager implements TokenManagerInterface
{
    /**
     * @var Signer
     */
    private $signer;

    /**
     * @var string
     */
    private $signatureKey;

    /**
     * @var string
     */
    private $verificationKey;

    /**
     * @var Parser
     */
    private $tokenParser;

    /**
     * @var CurrentTimeProviderInterface
     */
    private $currentTimeProvider;

    public function __construct(
        Signer $signer,
        string $signatureKey,
        string $verificationKey,
        Parser $tokenParser = null,
        CurrentTimeProviderInterface $currentTimeProvider = null
    ) {
        $this->signer = $signer;
        $this->signatureKey = $signatureKey;
        $this->verificationKey = $verificationKey;
        $this->tokenParser = $tokenParser ?: new Parser();
        $this->currentTimeProvider = $currentTimeProvider ?: new SystemTimeProvider();
    }

    /**
     * @param mixed $subject
     */
    public function getSignedToken($subject, int $lifetime, bool $endAtSession) : Token
    {
        $currentTimestamp = $this->currentTimeProvider->getCurrentTime()->getTimestamp();
        $builder = (new Builder())
            ->setIssuedAt($currentTimestamp)
            ->setExpiration($currentTimestamp + $lifetime)
            ->set(TokenManagerInterface::END_AT_SESSION_CLAIM, $endAtSession)
            ->set(TokenManagerInterface::SUBJECT_CLAIM, $subject)
            ->set(TokenManagerInterface::ISSUED_AT_CLAIM, $currentTimestamp);

        return $builder->sign($this->signer, $this->signatureKey)->getToken();
    }

    public function parseSignedToken(string $serializedToken) : Token
    {
        try {
            $token = $this->tokenParser->parse($serializedToken);
        } catch (Exception $e) {
            throw InvalidTokenException::fromMalformedToken($serializedToken, $e);
        }

        if (!$token->validate(new ValidationData($this->currentTimeProvider->getCurrentTime()->getTimestamp()))) {
            throw InvalidTokenException::fromExpiredToken($token);
        }

        if (!$token->verify($this->signer, $this->verificationKey)) {
            throw InvalidTokenException::fromIllegalToken($token);
        }

        return $token;
    }
}
