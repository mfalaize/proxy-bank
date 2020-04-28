<?php


namespace ProxyBank\Exceptions;


use Slim\Exception\HttpNotFoundException;

class UnknownAccountIdException extends ProxyBankException
{
    public function __construct(string $accountId)
    {
        parent::__construct(HttpNotFoundException::class, [$accountId]);
    }
}
