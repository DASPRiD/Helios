<?php
declare(strict_types=1);

namespace DASPRiD\HeliosTest;

use DASPRiD\Helios\CookieManager;
use DASPRiD\Helios\CurrentTime\CurrentTimeProviderInterface;
use DASPRiD\Helios\Exception\CookieNotFoundException;
use DASPRiD\Helios\Exception\InvalidTokenException;
use DASPRiD\Helios\TokenManagerInterface;
use DateTimeImmutable;
use Lcobucci\JWT\Token;
use PHPUnit_Framework_TestCase as TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\EmptyResponse;

class CookieManagerTest extends TestCase
{
    public function testInjectSecureTokenCookie()
    {
        $tokenManager = $this->prophesize(TokenManagerInterface::class);
        $tokenManager->getSignedToken('foo', 100, false)->willReturn(new Token());
        $cookieManager = $this->createCookieManager($tokenManager->reveal());

        $originalResponse = new EmptyResponse();
        $newResponse = $cookieManager->injectTokenCookie(
            $originalResponse,
            'foo',
            false
        );

        $this->assertSame([
            'helios=..; Path=/; Expires=Thu, 01 Jan 1970 00:03:20 GMT; Secure; HttpOnly',
        ], $newResponse->getHeader('Set-Cookie'));
    }

    public function testInjectNonSecureTokenCookie()
    {
        $tokenManager = $this->prophesize(TokenManagerInterface::class);
        $tokenManager->getSignedToken('foo', 100, false)->willReturn(new Token());
        $cookieManager = $this->createCookieManager($tokenManager->reveal(), false);

        $originalResponse = new EmptyResponse();
        $newResponse = $cookieManager->injectTokenCookie(
            $originalResponse,
            'foo',
            false
        );

        $this->assertSame([
            'helios=..; Path=/; Expires=Thu, 01 Jan 1970 00:03:20 GMT; HttpOnly',
        ], $newResponse->getHeader('Set-Cookie'));
    }

    public function testInjectTookenCookieExpiringEndOfSession()
    {
        $tokenManager = $this->prophesize(TokenManagerInterface::class);
        $tokenManager->getSignedToken('foo', 100, true)->willReturn(new Token());
        $cookieManager = $this->createCookieManager($tokenManager->reveal(), false);

        $originalResponse = new EmptyResponse();
        $newResponse = $cookieManager->injectTokenCookie(
            $originalResponse,
            'foo',
            true
        );

        $this->assertSame([
            'helios=..; Path=/; HttpOnly',
        ], $newResponse->getHeader('Set-Cookie'));
    }

    public function testExpireCookieIsNotOverwrittenWithSetFlag()
    {
        $tokenManager = $this->prophesize(TokenManagerInterface::class);
        $tokenManager->getSignedToken('foo', 100, false)->willReturn(new Token());
        $cookieManager = $this->createCookieManager($tokenManager->reveal(), false);

        $originalResponse = new EmptyResponse();
        $expireResponse = $cookieManager->expireTokenCookie($originalResponse);

        $newResponse = $cookieManager->injectTokenCookie(
            $expireResponse,
            'foo',
            false,
            false
        );

        $this->assertSame([
            'helios=; Path=/; Expires=Thu, 01 Jan 1970 00:00:01 GMT; HttpOnly',
        ], $newResponse->getHeader('Set-Cookie'));
    }

    public function testSecureExpireTokenCookie()
    {
        $cookieManager = $this->createCookieManager($this->prophesize(TokenManagerInterface::class)->reveal());

        $originalResponse = new EmptyResponse();
        $newResponse = $cookieManager->expireTokenCookie($originalResponse);

        $this->assertSame([
            'helios=; Path=/; Expires=Thu, 01 Jan 1970 00:00:01 GMT; Secure; HttpOnly',
        ], $newResponse->getHeader('Set-Cookie'));
    }

    public function testNonSecureExpireTokenCookie()
    {
        $cookieManager = $this->createCookieManager($this->prophesize(TokenManagerInterface::class)->reveal(), false);

        $originalResponse = new EmptyResponse();
        $newResponse = $cookieManager->expireTokenCookie($originalResponse);

        $this->assertSame([
            'helios=; Path=/; Expires=Thu, 01 Jan 1970 00:00:01 GMT; HttpOnly',
        ], $newResponse->getHeader('Set-Cookie'));
    }

    public function testHasValidTokenWithExistentValidToken()
    {
        $tokenManager = $this->prophesize(TokenManagerInterface::class);
        $tokenManager->parseSignedToken('foo')->willReturn(new Token());
        $cookieManager = $this->createCookieManager($tokenManager->reveal());

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getHeaderLine('Cookie')->willReturn('helios=foo');

        $this->assertTrue($cookieManager->hasValidToken($request->reveal()));
    }

    public function testHasValidTokenWithNonExistentToken()
    {
        $tokenManager = $this->prophesize(TokenManagerInterface::class);
        $tokenManager->parseSignedToken('foo')->willReturn(new Token());
        $cookieManager = $this->createCookieManager($tokenManager->reveal());

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getHeaderLine('Cookie')->willReturn('');

        $this->assertFalse($cookieManager->hasValidToken($request->reveal()));
    }

    public function testHasValidTokenWithInvalidToken()
    {
        $tokenManager = $this->prophesize(TokenManagerInterface::class);
        $tokenManager->parseSignedToken('foo')->willThrow(new InvalidTokenException());
        $cookieManager = $this->createCookieManager($tokenManager->reveal());

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getHeaderLine('Cookie')->willReturn('helios=foo');

        $this->assertFalse($cookieManager->hasValidToken($request->reveal()));
    }

    public function testGetTokenWithExistentToken()
    {
        $token = new Token([], []);
        $tokenManager = $this->prophesize(TokenManagerInterface::class);
        $tokenManager->parseSignedToken('foo')->willReturn($token);
        $cookieManager = $this->createCookieManager($tokenManager->reveal());

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getHeaderLine('Cookie')->willReturn('helios=foo');

        $this->assertSame($token, $cookieManager->getToken($request->reveal()));
    }

    public function testGetTokenWithNonExistentToken()
    {
        $cookieManager = $this->createCookieManager($this->prophesize(TokenManagerInterface::class)->reveal());

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getHeaderLine('Cookie')->willReturn('');

        $this->expectException(CookieNotFoundException::class);
        $this->expectExceptionMessage('Cookie with name "helios" was not found');
        $cookieManager->getToken($request->reveal());
    }

    private function createCookieManager(TokenManagerInterface $tokenManager, $secure = true) : CookieManager
    {
        if (null === $tokenManager) {
            $tokenManager = $this->prophesize(TokenManagerInterface::class)->reveal();
        }

        $currentTimeProvider = $this->prophesize(CurrentTimeProviderInterface::class);
        $currentTimeProvider->getCurrentTime()->willReturn(new DateTimeImmutable('@100'));

        return new CookieManager(
            'helios',
            $secure,
            100,
            $tokenManager,
            $currentTimeProvider->reveal()
        );
    }
}
