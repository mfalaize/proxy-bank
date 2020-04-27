<?php

use DI\ContainerBuilder;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use ProxyBank\Container;
use ProxyBank\Services\Banks\AnotherBank;
use ProxyBank\Services\Banks\CreditMutuelService;
use ProxyBank\Services\CryptoService;
use ProxyBank\Services\IntlService;
use Psr\Log\LoggerInterface;
use Slim\Factory\AppFactory;
use Slim\Views\PhpRenderer;
use function DI\autowire;
use function DI\create;

$builder = new ContainerBuilder(Container::class);
$builder->useAnnotations(true);
$builder->addDefinitions([
    LoggerInterface::class => autowire(Logger::class)
        ->constructorParameter("name", "ProxyBank")
        ->constructorParameter("handlers", [new StreamHandler("php://stdout", Logger::DEBUG)]),

    CryptoService::class => autowire(CryptoService::class)
        ->constructorParameter("srcDir", __DIR__),

    IntlService::class => create(IntlService::class),

    // Bank implementation services
    CreditMutuelService::getBank()->id => autowire(CreditMutuelService::class),
    PhpRenderer::class => autowire(PhpRenderer::class)
        ->constructor(__DIR__ . "/../templates/")
]);

AppFactory::setContainer($builder->build());
