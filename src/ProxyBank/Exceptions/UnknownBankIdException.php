<?php


namespace ProxyBank\Exceptions;


use Slim\Exception\HttpNotFoundException;

class UnknownBankIdException extends ProxyBankException
{
    public function __construct(string $bankId)
    {
        parent::__construct(HttpNotFoundException::class, [$bankId]);
    }
}
