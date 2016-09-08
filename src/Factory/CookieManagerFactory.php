<?php
declare(strict_types=1);

namespace DASPRiD\Helios\Factory;

use Assert\Assertion;
use DASPRiD\Helios\CookieManager;
use DASPRiD\Helios\TokenManagerInterface;
use Interop\Container\ContainerInterface;

final class CookieManagerFactory
{
    public function __invoke(ContainerInterface $container) : CookieManager
    {
        $config = $container->get('config');
        Assertion::keyIsset($config, 'helios');
        Assertion::keyIsset($config['helios'], 'cookie');
        Assertion::isArrayAccessible($config['helios']['cookie']);

        $cookieConfig = $config['helios']['cookie'];
        Assertion::keyExists($cookieConfig, 'name');
        Assertion::keyExists($cookieConfig, 'secure');
        Assertion::keyExists($cookieConfig, 'lifetime');

        return new CookieManager(
            $cookieConfig['name'],
            $cookieConfig['secure'],
            $cookieConfig['lifetime'],
            $container->get(TokenManagerInterface::class)
        );
    }
}
