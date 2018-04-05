<?php
declare(strict_types = 1);

namespace DASPRiD\Helios;

use CultuurNet\Clock\Clock;
use CultuurNet\Clock\SystemClock;
use DASPRiD\Helios\Identity\IdentityLookupInterface;
use DASPRiD\Pikkuleipa\Cookie;
use DateTimeZone;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class IdentityMiddleware implements MiddlewareInterface
{
    public const IDENTITY_ATTRIBUTE = 'helios-identity';

    /**
     * @var IdentityCookieManager
     */
    private $identityCookieManager;

    /**
     * @var IdentityLookupInterface
     */
    private $identityLookup;

    /**
     * @var int
     */
    private $refreshTime;

    /**
     * @var Clock
     */
    private $clock;

    public function __construct(
        IdentityCookieManager $identityCookieManager,
        IdentityLookupInterface $identityLookup,
        int $refreshTime,
        ?Clock $clock = null
    ) {
        $this->identityCookieManager = $identityCookieManager;
        $this->identityLookup = $identityLookup;
        $this->refreshTime = $refreshTime;
        $this->clock = $clock ?: new SystemClock(new DateTimeZone('utc'));
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler) : ResponseInterface
    {
        $cookie = $this->identityCookieManager->getCookie($request);
        $subject = $cookie->get(IdentityCookieManager::SUBJECT_CLAIM);

        if (null === $subject) {
            return $handler->handle($request);
        }

        $result = $this->identityLookup->lookup($subject);

        if (! $result->hasIdentity()) {
            return $handler->handle($request);
        }

        $nextResponse = $handler->handle($request->withAttribute(self::IDENTITY_ATTRIBUTE, $result->getIdentity()));

        if (! $this->shouldCookieBeRefreshed($cookie)) {
            return $nextResponse;
        }

        return $this->identityCookieManager->injectCookie($nextResponse, $subject, false, false);
    }

    private function shouldCookieBeRefreshed(Cookie $cookie) : bool
    {
        if ($cookie->endsWithSession()) {
            return false;
        }

        return $this->clock->getDateTime()->getTimestamp() >= (
            $cookie->getIssuedAt()->getTimestamp() + $this->refreshTime
        );
    }
}
