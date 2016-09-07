<?php
declare(strict_types=1);

namespace DASPRiD\Helios\Factory;

use Assert\Assertion;
use DASPRiD\Helios\CookieManager;
use DASPRiD\Helios\IdentityMiddleware;
use Interop\Container\ContainerInterface;

final class IdentityMiddlewareFactory
{
    public function __invoke(ContainerInterface $container) : CookieManager
    {
        $config = $container->get('config');
        Assertion::keyIsset($config, 'helios');
        Assertion::keyIsset($config['helios'], 'middleware');
        Assertion::isArrayAccessible($config['helios']['middleware']);

        $middlewareConfig = $config['helios']['middleware'];
        Assertion::keyExists($middlewareConfig, 'identity_lookup_service_name');
        Assertion::keyExists($middlewareConfig, 'refresh_time');

        return new IdentityMiddleware(
            $middlewareConfig['identity_lookup_service_name'],
            $config->get(CookieManager::class),
            $middlewareConfig['refresh_time']
        );
    }
}
