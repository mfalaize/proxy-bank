<?php

use DI\ContainerBuilder;
use ProxyBank\Container;
use ProxyBank\Services\CryptoService;
use ProxyBank\Services\IntlService;
use Slim\Factory\AppFactory;
use Slim\Views\PhpRenderer;
use function DI\autowire;
use function DI\create;

$env = getenv("ENVIRONMENT") === "production" ? "prod" : "dev";

$builder = new ContainerBuilder(Container::class);
$builder->useAnnotations(true);

$builder->addDefinitions(__DIR__ . "/config.$env.php");

$builder->addDefinitions([
    CryptoService::class => autowire(CryptoService::class)
        ->constructorParameter("srcDir", __DIR__),

    IntlService::class => create(IntlService::class),

    PhpRenderer::class => autowire(PhpRenderer::class)
        ->constructor(__DIR__ . "/../templates/")
]);

$builder->addDefinitions(__DIR__ . "/banks.php");

AppFactory::setContainer($builder->build());
