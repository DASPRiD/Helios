<?php
declare(strict_types=1);

namespace DASPRiD\HeliosTest;

use DASPRiD\Helios\CookieManagerInterface;
use DASPRiD\Helios\CurrentTime\CurrentTimeProviderInterface;
use DASPRiD\Helios\Identity\IdentityLookupInterface;
use DASPRiD\Helios\Identity\LookupResult;
use DASPRiD\Helios\IdentityMiddleware;
use DASPRiD\Helios\TokenManagerInterface;
use DateTimeImmutable;
use Lcobucci\JWT\Claim\Basic;
use Lcobucci\JWT\Token;
use PHPUnit_Framework_TestCase as TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\EmptyResponse;
use Zend\Diactoros\ServerRequest;

class IdentityMiddlewareTest extends TestCase
{
    public function testInvokeWithoutValidToken()
    {
        $request = new ServerRequest();
        $response = new EmptyResponse();

        $cookieManager = $this->prophesize(CookieManagerInterface::class);
        $cookieManager->hasValidToken($request)->willReturn(false);

        $middleware = new IdentityMiddleware(
            $this->prophesize(IdentityLookupInterface::class)->reveal(),
            $cookieManager->reveal(),
            30
        );

        $middleware($request, $response, function (ServerRequestInterface $request, ResponseInterface $response) {
            $this->assertNull($request->getAttribute(IdentityMiddleware::IDENTITY_ATTRIBUTE));
            return $response;
        });
    }

    public function testInvokeWithTokenWithoutSubject()
    {
        $request = new ServerRequest();
        $response = new EmptyResponse();

        $cookieManager = $this->prophesize(CookieManagerInterface::class);
        $cookieManager->hasValidToken($request)->willReturn(true);
        $cookieManager->getToken($request)->willReturn(new Token());

        $middleware = new IdentityMiddleware(
            $this->prophesize(IdentityLookupInterface::class)->reveal(),
            $cookieManager->reveal(),
            30
        );

        $middleware($request, $response, function (ServerRequestInterface $request, ResponseInterface $response) {
            $this->assertNull($request->getAttribute(IdentityMiddleware::IDENTITY_ATTRIBUTE));
            return $response;
        });
    }

    public function testInvokeWithInvalidIdentity()
    {
        $request = new ServerRequest();
        $response = new EmptyResponse();

        $identityLookup = $this->prophesize(IdentityLookupInterface::class);
        $identityLookup->lookup('foo')->willReturn(LookupResult::invalid());

        $cookieManager = $this->prophesize(CookieManagerInterface::class);
        $cookieManager->hasValidToken($request)->willReturn(true);
        $cookieManager->getToken($request)->willReturn(new Token([], [
            TokenManagerInterface::SUBJECT_CLAIM => new Basic(TokenManagerInterface::SUBJECT_CLAIM, 'foo'),
        ]));

        $middleware = new IdentityMiddleware(
            $identityLookup->reveal(),
            $cookieManager->reveal(),
            30
        );

        $middleware($request, $response, function (ServerRequestInterface $request, ResponseInterface $response) {
            $this->assertNull($request->getAttribute(IdentityMiddleware::IDENTITY_ATTRIBUTE));
            return $response;
        });
    }

    public function testInvokeWithValidIdentity()
    {
        $request = new ServerRequest();
        $response = new EmptyResponse();

        $identityLookup = $this->prophesize(IdentityLookupInterface::class);
        $identityLookup->lookup('foo')->willReturn(LookupResult::fromIdentity('bar'));

        $cookieManager = $this->prophesize(CookieManagerInterface::class);
        $cookieManager->hasValidToken($request)->willReturn(true);
        $cookieManager->getToken($request)->willReturn(new Token([], [
            TokenManagerInterface::SUBJECT_CLAIM => new Basic(TokenManagerInterface::SUBJECT_CLAIM, 'foo'),
        ]));

        $middleware = new IdentityMiddleware(
            $identityLookup->reveal(),
            $cookieManager->reveal(),
            30
        );

        $middleware($request, $response, function (ServerRequestInterface $request, ResponseInterface $response) {
            $this->assertSame('bar', $request->getAttribute(IdentityMiddleware::IDENTITY_ATTRIBUTE));
            return $response;
        });
    }

    public function refreshClaimProvider() : array
    {
        return [
            'no-refresh-without-end-of-session-claim' => [
                [],
                false,
            ],
            'no-refresh-with-true-end-of-session-claim' => [
                [
                    TokenManagerInterface::END_AT_SESSION_CLAIM => new Basic(
                        TokenManagerInterface::END_AT_SESSION_CLAIM,
                        true
                    ),
                ],
                false
            ],
            'no-refresh-without-issued-at-claim' => [
                [
                    TokenManagerInterface::END_AT_SESSION_CLAIM => new Basic(
                        TokenManagerInterface::END_AT_SESSION_CLAIM,
                        false
                    ),
                ],
                false
            ],
            'no-refresh-with-early-issued-at-claim' => [
                [
                    TokenManagerInterface::END_AT_SESSION_CLAIM => new Basic(
                        TokenManagerInterface::END_AT_SESSION_CLAIM,
                        false
                    ),
                    TokenManagerInterface::ISSUED_AT_CLAIM => new Basic(
                        TokenManagerInterface::ISSUED_AT_CLAIM,
                        71
                    ),
                ],
                false
            ],
            'refresh-with-old-issued-at-claim' => [
                [
                    TokenManagerInterface::END_AT_SESSION_CLAIM => new Basic(
                        TokenManagerInterface::END_AT_SESSION_CLAIM,
                        false
                    ),
                    TokenManagerInterface::ISSUED_AT_CLAIM => new Basic(
                        TokenManagerInterface::ISSUED_AT_CLAIM,
                        70
                    ),
                ],
                true
            ],
        ];
    }

    /**
     * @dataProvider refreshClaimProvider
     */
    public function testRefresh(array $claims, bool $expectRefresh)
    {
        $request = new ServerRequest();
        $response = new EmptyResponse();

        $identityLookup = $this->prophesize(IdentityLookupInterface::class);
        $identityLookup->lookup('foo')->willReturn(LookupResult::fromIdentity('bar'));

        $cookieManager = $this->prophesize(CookieManagerInterface::class);
        $cookieManager->hasValidToken($request)->willReturn(true);
        $cookieManager->getToken($request)->willReturn(new Token([], [
            TokenManagerInterface::SUBJECT_CLAIM => new Basic(TokenManagerInterface::SUBJECT_CLAIM, 'foo'),
        ] + $claims));

        if ($expectRefresh) {
            $cookieManager->injectTokenCookie($response, 'foo', false)->shouldBeCalled();
        } else {
            $cookieManager->injectTokenCookie()->shouldNotBeCalled();
        }

        $currentTimeProvider = $this->prophesize(CurrentTimeProviderInterface::class);
        $currentTimeProvider->getCurrentTime()->willReturn(new DateTimeImmutable('@100'));

        $middleware = new IdentityMiddleware(
            $identityLookup->reveal(),
            $cookieManager->reveal(),
            30,
            $currentTimeProvider->reveal()
        );

        $middleware($request, $response, function (ServerRequestInterface $request, ResponseInterface $response) {
            return $response;
        });
    }
}
