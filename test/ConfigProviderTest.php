<?php
declare(strict_types=1);

namespace DASPRiD\HeliosTest;

use DASPRiD\Helios\ConfigProvider;
use DASPRiD\Helios\CookieManagerInterface;
use DASPRiD\Helios\IdentityMiddleware;
use DASPRiD\Helios\TokenManagerInterface;
use PHPUnit_Framework_TestCase as TestCase;

class ConfigProviderTest extends TestCase
{
    public function testInvoke()
    {
        $this->assertSame([
            'dependencies' => (new ConfigProvider())->getDependencyConfig(),
        ], (new ConfigProvider())->__invoke());
    }

    public function testGetDependencyConfig()
    {
        $dependencyConfig = (new ConfigProvider())->getDependencyConfig();
        $this->assertArrayHasKey('factories', $dependencyConfig);
        $this->assertArrayHasKey(CookieManagerInterface::class, $dependencyConfig['factories']);
        $this->assertArrayHasKey(IdentityMiddleware::class, $dependencyConfig['factories']);
        $this->assertArrayHasKey(TokenManagerInterface::class, $dependencyConfig['factories']);
    }
}
