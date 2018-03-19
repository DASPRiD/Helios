<?php
declare(strict_types = 1);

namespace DASPRiD\HeliosTest\Factory;

use DASPRiD\Helios\Factory\IdentityMiddlewareFactory;
use DASPRiD\Helios\Identity\IdentityLookupInterface;
use DASPRiD\Helios\IdentityCookieManager;
use DASPRiD\Pikkuleipa\CookieManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class IdentityMiddlewareFactoryTest extends TestCase
{
    public function testInjection()
    {
        $container = $this->prophesize(ContainerInterface::class);
        $container->get('config')->willReturn([
            'helios' => [
                'identity_lookup_id' => 'foo',
                'refresh_time' => 100,
            ],
        ]);
        $identityCookieManager = new IdentityCookieManager(
            $this->prophesize(CookieManagerInterface::class)->reveal(),
            'helios'
        );
        $container->get(IdentityCookieManager::class)->willReturn($identityCookieManager);
        $identityLookup = $this->prophesize(IdentityLookupInterface::class)->reveal();
        $container->get('foo')->willReturn($identityLookup);

        $factory = new IdentityMiddlewareFactory();
        $identityMiddleware = $factory($container->reveal());

        $this->assertAttributeSame(100, 'refreshTime', $identityMiddleware);
        $this->assertAttributeSame($identityLookup, 'identityLookup', $identityMiddleware);
        $this->assertAttributeSame($identityCookieManager, 'identityCookieManager', $identityMiddleware);
    }
}
