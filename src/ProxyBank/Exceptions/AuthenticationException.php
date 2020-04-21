<?php


namespace ProxyBank\Exceptions;


use Slim\Exception\HttpUnauthorizedException;

class AuthenticationException extends ProxyBankException
{
    public function __construct(string $message)
    {
        parent::__construct(HttpUnauthorizedException::class, [$message]);
    }
}
