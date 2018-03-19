<?php
declare(strict_types = 1);

namespace DASPRiD\HeliosTest;

use DASPRiD\Helios\IdentityCookieManager;
use DASPRiD\Pikkuleipa\Cookie;
use DASPRiD\Pikkuleipa\CookieManagerInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\EmptyResponse;

class IdentityCookieManagerTest extends TestCase
{
    public function testGetCookie() : void
    {
        $request = $this->prophesize(ServerRequestInterface::class)->reveal();
        $cookie = new Cookie('helios');

        $cookieManager = $this->prophesize(CookieManagerInterface::class);
        $cookieManager->getCookie($request, 'helios')->willReturn($cookie);

        $manager = new IdentityCookieManager($cookieManager->reveal(), 'helios');
        $this->assertSame($cookie, $manager->getCookie($request));
    }

    public function testInjectCookie() : void
    {
        $response = new EmptyResponse();
        $expectedResponse = new EmptyResponse();

        $cookieManager = $this->prophesize(CookieManagerInterface::class);
        $cookieManager->setCookie($response, Argument::that(function (Cookie $cookie) : bool {
            return (
                'foo' === $cookie->get(IdentityCookieManager::SUBJECT_CLAIM) &&
                ! $cookie->endsWithSession()
            );
        }))->willReturn($expectedResponse);

        $manager = new IdentityCookieManager($cookieManager->reveal(), 'helios');
        $this->assertSame(
            $expectedResponse,
            $manager->injectCookie($response, 'foo')
        );
    }

    public function testInjectCookieWithEndsAtSession() : void
    {
        $response = new EmptyResponse();
        $expectedResponse = new EmptyResponse();

        $cookieManager = $this->prophesize(CookieManagerInterface::class);
        $cookieManager->setCookie($response, Argument::that(function (Cookie $cookie) : bool {
            return (
                'foo' === $cookie->get(IdentityCookieManager::SUBJECT_CLAIM) &&
                $cookie->endsWithSession()
            );
        }))->willReturn($expectedResponse);

        $manager = new IdentityCookieManager($cookieManager->reveal(), 'helios');
        $this->assertSame(
            $expectedResponse,
            $manager->injectCookie($response, 'foo', true)
        );
    }


    public function testExpireCookie() : void
    {
        $response = new EmptyResponse();
        $expectedResponse = new EmptyResponse();

        $cookieManager = $this->prophesize(CookieManagerInterface::class);
        $cookieManager->expireCookieByName($response, 'helios')->willReturn($expectedResponse);

        $manager = new IdentityCookieManager($cookieManager->reveal(), 'helios');
        $this->assertSame(
            $expectedResponse,
            $manager->expireCookie($response)
        );
    }
}
