<?php

use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

require __DIR__ . '/../src/dependencies.php';

$app = AppFactory::create();

require __DIR__ . '/../src/routes.php';

$app->run();
