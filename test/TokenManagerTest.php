<?php
declare(strict_types=1);

namespace DASPRiD\HeliosTest;

use DASPRiD\Helios\CurrentTime\CurrentTimeProviderInterface;
use DASPRiD\Helios\Exception\InvalidTokenException;
use DASPRiD\Helios\TokenManager;
use DASPRiD\Helios\TokenManagerInterface;
use DateTime;
use DateTimeImmutable;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use PHPUnit_Framework_TestCase as TestCase;

class TokenManagerTest extends TestCase
{
    public function testGetSignedToken()
    {
        $currentTimeProvider = $this->prophesize(CurrentTimeProviderInterface::class);
        $currentTimeProvider->getCurrentTime()->willReturn(new DateTimeImmutable('@100'));

        $tokenManager = new TokenManager(new Sha256(), 'foo', 'foo', new Parser(), $currentTimeProvider->reveal());
        $token = $tokenManager->getSignedToken('bar', 100, true);

        $this->assertSame(100, $token->getClaim(TokenManagerInterface::ISSUED_AT_CLAIM));
        $this->assertFalse($token->isExpired(new DateTime('@200')));
        $this->assertTrue($token->isExpired(new DateTime('@201')));
        $this->assertTrue($token->getClaim(TokenManagerInterface::END_AT_SESSION_CLAIM));
        $this->assertSame('bar', $token->getClaim(TokenManagerInterface::SUBJECT_CLAIM));
    }

    public function testParseProperlySignedToken()
    {
        $currentTimeProvider = $this->prophesize(CurrentTimeProviderInterface::class);
        $currentTimeProvider->getCurrentTime()->willReturn(new DateTimeImmutable('@100'));

        $tokenManager = new TokenManager(new Sha256(), 'foo', 'foo', new Parser(), $currentTimeProvider->reveal());
        $token = $tokenManager->parseSignedToken((string) $tokenManager->getSignedToken('bar', 100, true));

        $this->assertSame(100, $token->getClaim(TokenManagerInterface::ISSUED_AT_CLAIM));
        $this->assertFalse($token->isExpired(new DateTime('@200')));
        $this->assertTrue($token->isExpired(new DateTime('@201')));
        $this->assertTrue($token->getClaim(TokenManagerInterface::END_AT_SESSION_CLAIM));
        $this->assertSame('bar', $token->getClaim(TokenManagerInterface::SUBJECT_CLAIM));
    }

    public function testParseMalformedToken()
    {
        $tokenManager = new TokenManager(new Sha256(), 'foo', 'foo');
        $this->expectException(InvalidTokenException::class);
        $this->expectExceptionMessage('Token "foo" is malformed');
        $tokenManager->parseSignedToken('foo');
    }

    public function testParseExpiredToken()
    {
        $currentTimeProvider = $this->prophesize(CurrentTimeProviderInterface::class);
        $currentTimeProvider->getCurrentTime()->willReturn(new DateTimeImmutable('@100'));

        $tokenManager = new TokenManager(new Sha256(), 'foo', 'foo', new Parser(), $currentTimeProvider->reveal());
        $this->expectException(InvalidTokenException::class);
        $this->expectExceptionMessageRegExp('(^Token "[^"]+" has expired$)');
        $tokenManager->parseSignedToken((string) $tokenManager->getSignedToken('bar', -1, true));
    }

    public function testIllegalToken()
    {
        $currentTimeProvider = $this->prophesize(CurrentTimeProviderInterface::class);
        $currentTimeProvider->getCurrentTime()->willReturn(new DateTimeImmutable('@100'));

        $tokenManager = new TokenManager(new Sha256(), 'foo', 'bar', new Parser(), $currentTimeProvider->reveal());
        $this->expectException(InvalidTokenException::class);
        $this->expectExceptionMessageRegExp('(^Token "[^"]+" is not properly signed$)');
        $tokenManager->parseSignedToken((string) $tokenManager->getSignedToken('bar', 1, true));
    }
}
