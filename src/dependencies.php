<?php

use DI\ContainerBuilder;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use ProxyBank\Services\CryptoService;
use Psr\Log\LoggerInterface;
use Slim\Factory\AppFactory;
use function DI\autowire;

$builder = new ContainerBuilder();
$builder->useAnnotations(true);
$builder->addDefinitions([
    LoggerInterface::class => autowire(Logger::class)
        ->constructorParameter("name", "ProxyBank")
        ->constructorParameter("handlers", [new StreamHandler("php://stdout", Logger::DEBUG)]),

    CryptoService::class => autowire(CryptoService::class)
        ->constructorParameter("srcDir", __DIR__),
]);

AppFactory::setContainer($builder->build());
