<?php

use DI\ContainerBuilder;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use ProxyBank\Services\CryptoService;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerInterface;
use Slim\Factory\AppFactory;
use Slim\Psr7\Factory\StreamFactory;
use function DI\autowire;

$builder = new ContainerBuilder();
$builder->useAnnotations(true);
$builder->addDefinitions([
    LoggerInterface::class => autowire(Logger::class)
        ->constructorParameter("name", "ProxyBank")
        ->constructorParameter("handlers", [new StreamHandler("php://stdout", Logger::DEBUG)]),
    StreamFactoryInterface::class => autowire(StreamFactory::class),
    CryptoService::class => autowire(CryptoService::class)
        ->constructorParameter("srcDir", __DIR__),
]);

AppFactory::setContainer($builder->build());
