<?php


namespace ProxyBank\Exceptions;


use Slim\Exception\HttpBadRequestException;

class RequiredValueException extends ProxyBankException
{
    public function __construct(string $inputName)
    {
        parent::__construct(HttpBadRequestException::class, [$inputName]);
    }
}
