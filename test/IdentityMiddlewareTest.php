<?php
declare(strict_types = 1);

namespace DASPRiD\HeliosTest;

use CultuurNet\Clock\FrozenClock;
use DASPRiD\Helios\Identity\IdentityLookupInterface;
use DASPRiD\Helios\Identity\LookupResult;
use DASPRiD\Helios\IdentityCookieManager;
use DASPRiD\Helios\IdentityMiddleware;
use DASPRiD\Pikkuleipa\Cookie;
use DASPRiD\Pikkuleipa\CookieManagerInterface;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zend\Diactoros\Response\EmptyResponse;
use Zend\Diactoros\ServerRequest;

class IdentityMiddlewareTest extends TestCase
{
    public function testInvokeWithoutValidToken() : void
    {
        $request = new ServerRequest();
        $expectedResponse = new EmptyResponse();
        $cookie = new Cookie('helios');

        $cookieManager = $this->prophesize(CookieManagerInterface::class);
        $cookieManager->getCookie($request, 'helios')->willReturn($cookie);
        $manager = new IdentityCookieManager($cookieManager->reveal(), 'helios');

        $middleware = new IdentityMiddleware(
            $manager,
            $this->prophesize(IdentityLookupInterface::class)->reveal(),
            30
        );

        $handler = $this->prophesize(RequestHandlerInterface::class);
        $handler->handle(Argument::that(function (ServerRequestInterface $request) : bool {
            $this->assertNull($request->getAttribute(IdentityMiddleware::IDENTITY_ATTRIBUTE));
            return true;
        }))->willReturn($expectedResponse);

        $this->assertSame(
            $expectedResponse,
            $middleware->process($request, $handler->reveal())
        );
    }

    public function testInvokeWithInvalidIdentity() : void
    {
        $request = new ServerRequest();
        $expectedResponse = new EmptyResponse();
        $cookie = new Cookie('helios');
        $cookie->set(IdentityCookieManager::SUBJECT_CLAIM, 'foo');

        $cookieManager = $this->prophesize(CookieManagerInterface::class);
        $cookieManager->getCookie($request, 'helios')->willReturn($cookie);
        $manager = new IdentityCookieManager($cookieManager->reveal(), 'helios');

        $identityLookup = $this->prophesize(IdentityLookupInterface::class);
        $identityLookup->lookup('foo')->willReturn(LookupResult::invalid());

        $middleware = new IdentityMiddleware(
            $manager,
            $identityLookup->reveal(),
            30
        );

        $handler = $this->prophesize(RequestHandlerInterface::class);
        $handler->handle(Argument::that(function (ServerRequestInterface $request) : bool {
            $this->assertNull($request->getAttribute(IdentityMiddleware::IDENTITY_ATTRIBUTE));
            return true;
        }))->willReturn($expectedResponse);

        $this->assertSame(
            $expectedResponse,
            $middleware->process($request, $handler->reveal())
        );
    }

    public function testInvokeWithValidIdentity() : void
    {
        $request = new ServerRequest();
        $expectedResponse = new EmptyResponse();
        $cookie = new Cookie('helios');
        $cookie->set(IdentityCookieManager::SUBJECT_CLAIM, 'foo');

        $cookieManager = $this->prophesize(CookieManagerInterface::class);
        $cookieManager->getCookie($request, 'helios')->willReturn($cookie);
        $manager = new IdentityCookieManager($cookieManager->reveal(), 'helios');

        $identityLookup = $this->prophesize(IdentityLookupInterface::class);
        $identityLookup->lookup('foo')->willReturn(LookupResult::fromIdentity('bar'));

        $middleware = new IdentityMiddleware(
            $manager,
            $identityLookup->reveal(),
            30
        );

        $handler = $this->prophesize(RequestHandlerInterface::class);
        $handler->handle(Argument::that(function (ServerRequestInterface $request) : bool {
            $this->assertSame('bar', $request->getAttribute(IdentityMiddleware::IDENTITY_ATTRIBUTE));
            return true;
        }))->willReturn($expectedResponse);

        $this->assertSame(
            $expectedResponse,
            $middleware->process($request, $handler->reveal())
        );
    }

    public static function refreshProvider() : array
    {
        return [
            'no-refresh-with-early-datetime' => [
                new DateTimeImmutable('2018-01-01 12:00:29 UTC'),
                false,
                false
            ],
            'refresh-with-late-datetime' => [
                new DateTimeImmutable('2018-01-01 12:00:30 UTC'),
                false,
                true
            ],
            'no-refresh-with-ends-at-session' => [
                new DateTimeImmutable('2018-01-01 12:00:30 UTC'),
                true,
                false
            ],
        ];
    }

    /**
     * @dataProvider refreshProvider
     */
    public function testRefresh(DateTimeImmutable $currentTime, bool $endsAtSession, bool $shouldRefresh) : void
    {
        $request = new ServerRequest();
        $handlerResponse = new EmptyResponse();
        $expectedResponse = $handlerResponse;
        $refreshResponse = new EmptyResponse();

        if ($shouldRefresh) {
            $expectedResponse = $refreshResponse;
        }

        $cookie = new Cookie('helios', $endsAtSession, new DateTimeImmutable('2018-01-01 12:00:00 UTC'));
        $cookie->set(IdentityCookieManager::SUBJECT_CLAIM, 'foo');

        $cookieManager = $this->prophesize(CookieManagerInterface::class);
        $cookieManager->getCookie($request, 'helios')->willReturn($cookie);
        $cookieManager->setCookie($handlerResponse, Argument::that(function (Cookie $cookie) : bool {
            return 'foo' === $cookie->get(IdentityCookieManager::SUBJECT_CLAIM);
        }), false)->willReturn($refreshResponse);
        $manager = new IdentityCookieManager($cookieManager->reveal(), 'helios');

        $identityLookup = $this->prophesize(IdentityLookupInterface::class);
        $identityLookup->lookup('foo')->willReturn(LookupResult::fromIdentity('bar'));

        $middleware = new IdentityMiddleware(
            $manager,
            $identityLookup->reveal(),
            30,
            new FrozenClock($currentTime)
        );

        $handler = $this->prophesize(RequestHandlerInterface::class);
        $handler->handle(Argument::that(function (ServerRequestInterface $request) : bool {
            $this->assertSame('bar', $request->getAttribute(IdentityMiddleware::IDENTITY_ATTRIBUTE));
            return true;
        }))->willReturn($handlerResponse);

        $this->assertSame(
            $expectedResponse,
            $middleware->process($request, $handler->reveal())
        );
    }
}
