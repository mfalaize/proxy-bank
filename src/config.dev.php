<?php

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\PsrLogMessageProcessor;
use Psr\Log\LoggerInterface;
use function DI\autowire;

return [
    "env" => "dev",

    LoggerInterface::class => autowire(Logger::class)
        ->constructorParameter("name", "ProxyBank")
        ->constructorParameter("handlers", [
            new StreamHandler("php://stdout", Logger::DEBUG)
        ])
        ->constructorParameter("processors", [
            new PsrLogMessageProcessor()
        ]),
];
