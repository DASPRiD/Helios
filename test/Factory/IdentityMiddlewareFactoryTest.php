<?php
declare(strict_types=1);

namespace DASPRiD\HeliosTest\Factory;

use DASPRiD\Helios\CookieManagerInterface;
use DASPRiD\Helios\Factory\IdentityMiddlewareFactory;
use DASPRiD\Helios\Identity\IdentityLookupInterface;
use Interop\Container\ContainerInterface;
use PHPUnit_Framework_TestCase as TestCase;

class IdentityMiddlewareFactoryTest extends TestCase
{
    public function testInjection()
    {
        $container = $this->prophesize(ContainerInterface::class);
        $container->get('config')->willReturn([
            'helios' => [
                'middleware' => [
                    'identity_lookup_service_name' => 'foo',
                    'refresh_time' => 100,
                ],
            ],
        ]);
        $cookieManager = $this->prophesize(CookieManagerInterface::class)->reveal();
        $container->get(CookieManagerInterface::class)->willReturn($cookieManager);
        $identityLookup = $this->prophesize(IdentityLookupInterface::class)->reveal();
        $container->get('foo')->willReturn($identityLookup);

        $factory = new IdentityMiddlewareFactory();
        $identityMiddleware = $factory($container->reveal());

        $this->assertAttributeSame(100, 'refreshTime', $identityMiddleware);
        $this->assertAttributeSame($identityLookup, 'identityLookup', $identityMiddleware);
        $this->assertAttributeSame($cookieManager, 'cookieManager', $identityMiddleware);
    }
}
