<?php
declare(strict_types = 1);

namespace DASPRiD\HeliosTest\Factory;

use DASPRiD\Helios\Factory\IdentityCookieManagerFactory;
use DASPRiD\Pikkuleipa\CookieManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class IdentityCookieManagerFactoryTest extends TestCase
{
    public function testInjection()
    {
        $container = $this->prophesize(ContainerInterface::class);
        $container->get('config')->willReturn([
            'helios' => [
                'cookie_name' => 'helios',
            ],
        ]);
        $cookieManager = $this->prophesize(CookieManagerInterface::class)->reveal();
        $container->get(CookieManagerInterface::class)->willReturn($cookieManager);

        $factory = new IdentityCookieManagerFactory();
        $identityCookieManager = $factory($container->reveal());

        $this->assertAttributeSame($cookieManager, 'cookieManager', $identityCookieManager);
        $this->assertAttributeSame('helios', 'cookieName', $identityCookieManager);
    }
}
