<?php

use ProxyBank\Controllers\CryptoController;

$app->post('/encrypt', CryptoController::class . ":encrypt");
