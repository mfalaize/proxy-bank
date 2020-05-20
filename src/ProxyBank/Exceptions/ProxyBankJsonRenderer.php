<?php


namespace ProxyBank\Exceptions;


use Slim\Error\Renderers\JsonErrorRenderer;

class ProxyBankJsonRenderer extends JsonErrorRenderer
{
    public function __construct()
    {
        $this->defaultErrorTitle = "An unexpected error has occurred. Please contact the website administrator.";
    }
}
