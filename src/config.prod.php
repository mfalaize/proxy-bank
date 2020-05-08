<?php

use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;
use Monolog\Processor\GitProcessor;
use Monolog\Processor\PsrLogMessageProcessor;
use Monolog\Processor\UidProcessor;
use Monolog\Processor\WebProcessor;
use Psr\Log\LoggerInterface;
use function DI\autowire;

return [
    "env" => "prod",

    LoggerInterface::class => autowire(Logger::class)
        ->constructorParameter("name", "ProxyBank")
        ->constructorParameter("handlers", [
            new RotatingFileHandler(__DIR__ . "/../logs/ProxyBank.log", 7, Logger::INFO)
        ])
        ->constructorParameter("processors", [
            new WebProcessor(),
            new UidProcessor(),
            new GitProcessor(),
            new PsrLogMessageProcessor()
        ]),
];
