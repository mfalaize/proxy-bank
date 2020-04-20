<?php

use ProxyBank\Middlewares\IntlMiddleware;
use Psr\Log\LoggerInterface;
use Slim\Middleware\ContentLengthMiddleware;

$app->addMiddleware(new ContentLengthMiddleware());
$app->addBodyParsingMiddleware();
$app->addMiddleware(new IntlMiddleware($app->getContainer()));
$app->addErrorMiddleware(false, true, true, $app->getContainer()->get(LoggerInterface::class));
