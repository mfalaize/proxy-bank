<?php


namespace ProxyBank\Exceptions;


use Exception;

abstract class ProxyBankException extends Exception
{
    /**
     * @var string
     */
    public $httpExceptionClass;

    /**
     * @var array
     */
    public $messageFormatterArgs;

    /**
     * ProxyBankException constructor.
     * @param string $httpExceptionClass
     * @param array $messageFormatterArgs
     */
    public function __construct(string $httpExceptionClass, array $messageFormatterArgs = [])
    {
        parent::__construct();
        $this->httpExceptionClass = $httpExceptionClass;
        $this->messageFormatterArgs = $messageFormatterArgs;
    }
}
