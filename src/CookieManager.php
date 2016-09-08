<?php
declare(strict_types=1);

namespace DASPRiD\Helios;

use DASPRiD\Helios\CurrentTime\CurrentTimeProviderInterface;
use DASPRiD\Helios\CurrentTime\SystemTimeProvider;
use DASPRiD\Helios\Exception\CookieNotFoundException;
use DASPRiD\Helios\Exception\InvalidTokenException;
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

    /**
     * @param mixed $subject
     */
    public function injectTokenCookie(ResponseInterface $response, $subject, bool $endAtSession) : ResponseInterface
    {
        $currentTimestamp = $this->currentTimeProvider->getCurrentTime()->getTimestamp();
        $setCookie = SetCookie::create($this->cookieName)
            ->withHttpOnly(true)
            ->withPath('/')
            ->withExpires($endAtSession ? null : $currentTimestamp + $this->lifetime);

        if ($this->secure) {
            $setCookie = $setCookie->withSecure(true);
        }

        return FigResponseCookies::set(
            $response,
            $setCookie->withValue($this->tokenManager->getSignedToken($subject, $this->lifetime, $endAtSession))
        );
    }

    public function expireTokenCookie(ResponseInterface $response) : ResponseInterface
    {
        return FigResponseCookies::set(
            $response,
            SetCookie::create($this->cookieName)
                ->withHttpOnly(true)
                ->withPath('/')
                ->withExpires(0)
                ->withValue(null)
        );
    }

    public function hasValidToken(ServerRequestInterface $request) : bool
    {
        $cookies = $request->getCookieParams();

        if (!array_key_exists($this->cookieName, $cookies)) {
            return false;
        }

        try {
            $this->tokenManager->parseSignedToken($cookies[$this->cookieName]);
        } catch (InvalidTokenException $e) {
            return false;
        }

        return true;
    }

    public function getToken(ServerRequestInterface $request) : Token
    {
        $cookies = $request->getCookieParams();

        if (!array_key_exists($this->cookieName, $cookies)) {
            throw CookieNotFoundException::fromNonExistentCookie($this->cookieName);
        }

        return $this->tokenManager->parseSignedToken($cookies[$this->cookieName]);
    }
}
