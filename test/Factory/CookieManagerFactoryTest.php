<?php
declare(strict_types=1);

namespace DASPRiD\HeliosTest\Factory;

use DASPRiD\Helios\Factory\CookieManagerFactory;
use DASPRiD\Helios\TokenManagerInterface;
use Interop\Container\ContainerInterface;
use PHPUnit_Framework_TestCase as TestCase;

class CookieManagerFactoryTest extends TestCase
{
    public function testInjection()
    {
        $container = $this->prophesize(ContainerInterface::class);
        $container->get('config')->willReturn([
            'helios' => [
                'cookie' => [
                    'name' => 'foobar',
                    'secure' => true,
                    'lifetime' => 100,
                ],
            ],
        ]);
        $tokenManager = $this->prophesize(TokenManagerInterface::class)->reveal();
        $container->get(TokenManagerInterface::class)->willReturn($tokenManager);

        $factory = new CookieManagerFactory();
        $cookieManager = $factory($container->reveal());

        $this->assertAttributeSame('foobar', 'cookieName', $cookieManager);
        $this->assertAttributeSame(true, 'secure', $cookieManager);
        $this->assertAttributeSame(100, 'lifetime', $cookieManager);
        $this->assertAttributeSame($tokenManager, 'tokenManager', $cookieManager);
    }
}
