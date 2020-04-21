<?php


namespace ProxyBank\Exceptions;


use Slim\Exception\HttpBadRequestException;

class UnknownBankIdException extends ProxyBankException
{
    public function __construct(string $bankId)
    {
        parent::__construct(HttpBadRequestException::class, [$bankId]);
    }
}
