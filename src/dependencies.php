<?php

use DI\ContainerBuilder;
use Monolog\Logger;
use ProxyBank\Services\CryptoService;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerInterface;
use Slim\Factory\AppFactory;
use Slim\Psr7\Factory\StreamFactory;
use function DI\create;

$builder = new ContainerBuilder();
$builder->useAnnotations(true);
$builder->addDefinitions([
    LoggerInterface::class => create(Logger::class),
    StreamFactoryInterface::class => create(StreamFactory::class),
    CryptoService::class => create(CryptoService::class)->constructor(__DIR__ . '/..'),
]);

AppFactory::setContainer($builder->build());
