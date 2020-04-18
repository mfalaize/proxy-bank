<?php

use ProxyBank\Middlewares\IntlMiddleware;

$app->addMiddleware(new IntlMiddleware($app->getContainer()));
