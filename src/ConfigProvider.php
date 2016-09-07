<?php
declare(strict_types=1);

namespace DASPRiD\Helios;

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
                CookieManager::class => Factory\CookieManagerFactory::class,
                IdentityMiddleware::class => Factory\IdentityMiddlewareFactory::class,
                TokenManager::class => Factory\TokenManagerFactory::class,
            ],
        ];
    }
}
