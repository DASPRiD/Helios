<?php
declare(strict_types=1);

namespace DASPRiD\Helios;

use Lcobucci\JWT\Token;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

interface CookieManagerInterface
{
    /**
     * @param mixed $subject
     */
    public function injectTokenCookie(ResponseInterface $response, $subject, bool $endAtSession) : ResponseInterface;

    public function expireTokenCookie(ResponseInterface $response) : ResponseInterface;

    public function hasValidToken(ServerRequestInterface $request) : bool;

    public function getToken(ServerRequestInterface $request) : Token;
}
