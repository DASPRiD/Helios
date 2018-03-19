<?php
declare(strict_types = 1);

namespace DASPRiD\Helios\Factory;

use DASPRiD\Helios\IdentityCookieManager;
use DASPRiD\Helios\IdentityMiddleware;
use DASPRiD\TreeReader\TreeReader;
use Psr\Container\ContainerInterface;

final class IdentityMiddlewareFactory
{
    public function __invoke(ContainerInterface $container) : IdentityMiddleware
    {
        $config = (new TreeReader($container->get('config')))->getChildren('helios');

        return new IdentityMiddleware(
            $container->get(IdentityCookieManager::class),
            $container->get($config->getString('identity_lookup_id')),
            $config->getInt('refresh_time')
        );
    }
}
