<?php


namespace ProxyBank\Exceptions;


use Slim\Exception\HttpBadRequestException;

class UnknownAccountIdException extends ProxyBankException
{
    public function __construct(string $accountId)
    {
        parent::__construct(HttpBadRequestException::class, [$accountId]);
    }
}
