<?php
declare(strict_types=1);

namespace DASPRiD\Helios;

use DASPRiD\Helios\CurrentTime\CurrentTimeProviderInterface;
use DASPRiD\Helios\CurrentTime\SystemTimeProvider;
use DASPRiD\Helios\Exception\CookieNotFoundException;
use DASPRiD\Helios\Exception\InvalidTokenException;
use Dflydev\FigCookies\FigRequestCookies;
use Dflydev\FigCookies\FigResponseCookies;
use Dflydev\FigCookies\SetCookie;
use Lcobucci\JWT\Token;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class CookieManager implements CookieManagerInterface
{
    /**
     * @var string
     */
    private $cookieName;

    /**
     * @var bool
     */
    private $secure;

    /**
     * @var int|null
     */
    private $lifetime;

    /**
     * @var TokenManagerInterface
     */
    private $tokenManager;

    /**
     * @var CurrentTimeProviderInterface
     */
    private $currentTimeProvider;

    public function __construct(
        string $cookieName,
        bool $secure,
        int $lifetime,
        TokenManagerInterface $tokenManager,
        CurrentTimeProviderInterface $currentTimeProvider = null
    ) {
        $this->cookieName = $cookieName;
        $this->secure = $secure;
        $this->lifetime = $lifetime;
        $this->tokenManager = $tokenManager;
        $this->currentTimeProvider = $currentTimeProvider ?: new SystemTimeProvider();
    }

    public function injectTokenCookie(
        ResponseInterface $response,
        $subject,
        bool $endAtSession,
        bool $overwriteExpireCookie = true
    ) : ResponseInterface {
        if (!$overwriteExpireCookie && '' === FigResponseCookies::get($response, $this->cookieName)->getValue()) {
            return $response;
        }

        $currentTimestamp = $this->currentTimeProvider->getCurrentTime()->getTimestamp();
        $setCookie = SetCookie::create($this->cookieName)
            ->withHttpOnly(true)
            ->withPath('/')
            ->withExpires($endAtSession ? null : $currentTimestamp + $this->lifetime)
            ->withSecure($this->secure);

        return FigResponseCookies::set(
            $response,
            $setCookie->withValue($this->tokenManager->getSignedToken($subject, $this->lifetime, $endAtSession))
        );
    }

    public function expireTokenCookie(ResponseInterface $response) : ResponseInterface
    {
        $setCookie = SetCookie::create($this->cookieName)
            ->withHttpOnly(true)
            ->withPath('/')
            ->withExpires(1)
            ->withSecure($this->secure)
            ->withValue('');

        return FigResponseCookies::set($response, $setCookie);
    }

    public function hasValidToken(ServerRequestInterface $request) : bool
    {
        $requestCookie = FigRequestCookies::get($request, $this->cookieName);
        $cookieValue = $requestCookie->getValue();

        if (null === $cookieValue) {
            return false;
        }

        try {
            $this->tokenManager->parseSignedToken($cookieValue);
        } catch (InvalidTokenException $e) {
            return false;
        }

        return true;
    }

    public function getToken(ServerRequestInterface $request) : Token
    {
        $requestCookie = FigRequestCookies::get($request, $this->cookieName);
        $cookieValue = $requestCookie->getValue();

        if (null === $cookieValue) {
            throw CookieNotFoundException::fromNonExistentCookie($this->cookieName);
        }

        return $this->tokenManager->parseSignedToken($cookieValue);
    }
}
