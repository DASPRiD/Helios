<?php
declare(strict_types=1);

namespace DASPRiD\Helios\Factory;

use Assert\Assertion;
use DASPRiD\Helios\CookieManagerInterface;
use DASPRiD\Helios\IdentityMiddleware;
use Interop\Container\ContainerInterface;

final class IdentityMiddlewareFactory
{
    public function __invoke(ContainerInterface $container) : IdentityMiddleware
    {
        $config = $container->get('config');
        Assertion::keyIsset($config, 'helios');
        Assertion::keyIsset($config['helios'], 'middleware');
        Assertion::isArrayAccessible($config['helios']['middleware']);

        $middlewareConfig = $config['helios']['middleware'];
        Assertion::keyExists($middlewareConfig, 'identity_lookup_id');
        Assertion::keyExists($middlewareConfig, 'refresh_time');

        return new IdentityMiddleware(
            $container->get($middlewareConfig['identity_lookup_id']),
            $container->get(CookieManagerInterface::class),
            $middlewareConfig['refresh_time']
        );
    }
}
