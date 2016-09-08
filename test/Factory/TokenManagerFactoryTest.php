<?php
declare(strict_types=1);

namespace DASPRiD\HeliosTest\Factory;

use DASPRiD\Helios\Factory\TokenManagerFactory;
use Interop\Container\ContainerInterface;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use PHPUnit_Framework_TestCase as TestCase;

class TokenManagerFactoryTest extends TestCase
{
    public function testInjection()
    {
        $container = $this->prophesize(ContainerInterface::class);
        $container->get('config')->willReturn([
            'helios' => [
                'token' => [
                    'signer_class' => Sha256::class,
                    'signature_key' => 'foo',
                    'verification_key' => 'bar',
                ],
            ],
        ]);

        $factory = new TokenManagerFactory();
        $tokenManager = $factory($container->reveal());

        $this->assertAttributeInstanceOf(Sha256::class, 'signer', $tokenManager);
        $this->assertAttributeSame('foo', 'signatureKey', $tokenManager);
        $this->assertAttributeSame('bar', 'verificationKey', $tokenManager);
    }
}
