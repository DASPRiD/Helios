<?php
declare(strict_types = 1);

namespace DASPRiD\Helios\Factory;

use DASPRiD\Helios\IdentityCookieManager;
use DASPRiD\Pikkuleipa\CookieManagerInterface;
use DASPRiD\TreeReader\TreeReader;
use Psr\Container\ContainerInterface;

final class IdentityCookieManagerFactory
{
    public function __invoke(ContainerInterface $container) : IdentityCookieManager
    {
        $config = (new TreeReader($container->get('config')))->getChildren('helios');

        return new IdentityCookieManager(
            $container->get(CookieManagerInterface::class),
            $config->getString('cookie_name')
        );
    }
}
