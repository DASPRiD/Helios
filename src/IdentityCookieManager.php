<?php
declare(strict_types = 1);

namespace DASPRiD\Helios;

use DASPRiD\Pikkuleipa\Cookie;
use DASPRiD\Pikkuleipa\CookieManagerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class IdentityCookieManager
{
    public const SUBJECT_CLAIM = 'subject';

    /**
     * @var CookieManagerInterface
     */
    private $cookieManager;

    /**
     * @var string
     */
    private $cookieName;

    public function __construct(CookieManagerInterface $cookieManager, string $cookieName)
    {
        $this->cookieManager = $cookieManager;
        $this->cookieName = $cookieName;
    }

    public function getCookie(ServerRequestInterface $request)
    {
        return $this->cookieManager->getCookie($request, $this->cookieName);
    }

    public function injectCookie(
        ResponseInterface $response,
        $subject,
        bool $endsAtSession = false,
        bool $overwriteExpireCookie = true
    ) : ResponseInterface {
        $cookie = new Cookie($this->cookieName, $endsAtSession);
        $cookie->set(self::SUBJECT_CLAIM, $subject);

        return $this->cookieManager->setCookie($response, $cookie, $overwriteExpireCookie);
    }

    public function expireCookie(ResponseInterface $response) : ResponseInterface
    {
        return $this->cookieManager->expireCookieByName($response, $this->cookieName);
    }
}
