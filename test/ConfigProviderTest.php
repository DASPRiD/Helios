<?php
declare(strict_types = 1);

namespace DASPRiD\HeliosTest;

use DASPRiD\Helios\ConfigProvider;
use DASPRiD\Helios\IdentityCookieManager;
use DASPRiD\Helios\IdentityMiddleware;
use PHPUnit\Framework\TestCase;

class ConfigProviderTest extends TestCase
{
    public function testInvoke() : void
    {
        $this->assertSame([
            'dependencies' => (new ConfigProvider())->getDependencyConfig(),
        ], (new ConfigProvider())->__invoke());
    }

    public function testGetDependencyConfig() : void
    {
        $dependencyConfig = (new ConfigProvider())->getDependencyConfig();
        $this->assertArrayHasKey('factories', $dependencyConfig);
        $this->assertArrayHasKey(IdentityCookieManager::class, $dependencyConfig['factories']);
        $this->assertArrayHasKey(IdentityMiddleware::class, $dependencyConfig['factories']);
    }
}
