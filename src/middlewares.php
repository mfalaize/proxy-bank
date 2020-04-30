<?php

use ProxyBank\Exceptions\ProxyBankException;
use ProxyBank\Exceptions\ProxyBankExceptionsHandler;
use ProxyBank\Middlewares\IntlMiddleware;
use ProxyBank\Services\IntlService;
use Psr\Log\LoggerInterface;

$app->addBodyParsingMiddleware();
$app->addMiddleware(new IntlMiddleware($app->getContainer()));

$errorMiddleware = $app->addErrorMiddleware(false, true, true, $app->getContainer()->get(LoggerInterface::class));
$proxyBankExceptionsHandler = new ProxyBankExceptionsHandler(
    $app->getContainer()->get(IntlService::class),
    $app->getCallableResolver(),
    $app->getResponseFactory(),
    $app->getContainer()->get(LoggerInterface::class)
);
$errorMiddleware->setErrorHandler(ProxyBankException::class, $proxyBankExceptionsHandler, true);
