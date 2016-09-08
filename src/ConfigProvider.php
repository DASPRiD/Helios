<?php
declare(strict_types=1);

namespace DASPRiD\Helios;

use DASPRiD\Helios\Factory\CookieManagerFactory;
use DASPRiD\Helios\Factory\IdentityMiddlewareFactory;
use DASPRiD\Helios\Factory\TokenManagerFactory;

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
                CookieManagerInterface::class => CookieManagerFactory::class,
                IdentityMiddleware::class => IdentityMiddlewareFactory::class,
                TokenManagerInterface::class => TokenManagerFactory::class,
            ],
        ];
    }
}
