<?php


namespace ProxyBank\Exceptions;


use Slim\Exception\HttpBadRequestException;

class EmptyParsedBodyException extends ProxyBankException
{
    public function __construct()
    {
        parent::__construct(HttpBadRequestException::class);
    }
}
