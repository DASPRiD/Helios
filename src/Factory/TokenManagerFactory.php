<?php
declare(strict_types=1);

namespace DASPRiD\Helios\Factory;

use Assert\Assertion;
use DASPRiD\Helios\CookieManager;
use DASPRiD\Helios\TokenManager;
use Interop\Container\ContainerInterface;

final class TokenManagerFactory
{
    public function __invoke(ContainerInterface $container) : CookieManager
    {
        $config = $container->get('config');
        Assertion::keyIsset($config, 'helios');
        Assertion::keyIsset($config['helios'], 'token');
        Assertion::isArrayAccessible($config['helios']['token']);

        $tokenConfig = $config['helios']['token'];
        Assertion::keyExists($tokenConfig, 'signer_class');
        Assertion::keyExists($tokenConfig, 'signature_key');
        Assertion::keyExists($tokenConfig, 'verification_key');
        Assertion::classExists($tokenConfig['signer_class']);

        return new TokenManager(
            new $tokenConfig['signer_class'](),
            $tokenConfig['signature_key'],
            $tokenConfig['verification_key']
        );
    }
}
