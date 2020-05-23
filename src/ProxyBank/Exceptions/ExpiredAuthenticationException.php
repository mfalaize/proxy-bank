<?php


namespace ProxyBank\Exceptions;


use Slim\Exception\HttpUnauthorizedException;

class ExpiredAuthenticationException extends ProxyBankException
{
    public function __construct(string $bankName)
    {
        parent::__construct(HttpUnauthorizedException::class, [$bankName]);
    }
}
