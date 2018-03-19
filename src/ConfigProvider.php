<?php
declare(strict_types = 1);

namespace DASPRiD\Helios;

use DASPRiD\Helios\Factory\IdentityCookieManagerFactory;
use DASPRiD\Helios\Factory\IdentityMiddlewareFactory;

final class ConfigProvider
{
    public function __invoke() : array
    {
        return [
            'dependencies' => $this->getDependencyConfig(),
        ];
    }

    public function getDependencyConfig() : array
    {
        return [
            'factories' => [
                IdentityCookieManager::class => IdentityCookieManagerFactory::class,
                IdentityMiddleware::class => IdentityMiddlewareFactory::class,
            ],
        ];
    }
}
