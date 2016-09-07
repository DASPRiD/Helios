<?php
declare(strict_types=1);

namespace DASPRiD\Helios;

use DASPRiD\Helios\CurrentTime\CurrentTimeProviderInterface;
use DASPRiD\Helios\CurrentTime\SystemTimeProvider;
use DASPRiD\Helios\Identity\IdentityLookupInterface;
use Lcobucci\JWT\Token;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class IdentityMiddleware
{
    const IDENTITY_ATTRIBUTE = 'helios-identity';

    /**
     * @var IdentityLookupInterface
     */
    private $identityLookup;

    /**
     * @var CookieManagerInterface
     */
    private $cookieManager;

    /**
     * @var int
     */
    private $refreshTime;

    /**
     * @var CurrentTimeProviderInterface
     */
    private $currentTimeProvider;

    public function __construct(
        IdentityLookupInterface $identityLookup,
        CookieManagerInterface $cookieManager,
        int $refreshTime,
        CurrentTimeProviderInterface $currentTimeProvider = null
    ) {
        $this->identityLookup = $identityLookup;
        $this->cookieManager = $cookieManager;
        $this->refreshTime = $refreshTime;
        $this->currentTimeProvider = $currentTimeProvider ?: new SystemTimeProvider();
    }

    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response,
        callable $next
    ) : ResponseInterface {
        if (!$this->cookieManager->hasValidToken($request)) {
            return $next($request, $response);
        }

        $token = $this->cookieManager->getToken($request);

        if (!$token->hasClaim(TokenManager::SUBJECT_CLAIM)) {
            return $next($request, $response);
        }

        $subject = $token->getClaim(TokenManager::SUBJECT_CLAIM);
        $result = $this->identityLookup->lookup($subject);

        if (!$result->hasIdentity()) {
            return $next($request, $response);
        }

        $nextResponse = $next($request->withAttribute(self::IDENTITY_ATTRIBUTE, $result->getIdentity()), $response);

        if (!$this->shouldTokenBeRefreshed($token)) {
            return $nextResponse;
        }

        return $this->cookieManager->injectTokenCookie(
            $nextResponse,
            $subject,
            false
        );
    }

    private function shouldTokenBeRefreshed(Token $token) : bool
    {
        if ($token->hasClaim(TokenManager::END_AT_SESSION_CLAIM)
            && $token->getClaim(TokenManager::END_AT_SESSION_CLAIM)
        ) {
            return false;
        }

        if (!$token->hasClaim(TokenManager::ISSUED_AT_CLAIM)) {
            return false;
        }

        return $this->currentTimeProvider->getCurrentTime()->getTimestamp() >= (
            $token->getClaim(TokenManager::ISSUED_AT_CLAIM) + $this->refreshTime
        );
    }
}
