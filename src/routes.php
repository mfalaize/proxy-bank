<?php

use ProxyBank\Controllers\BankController;
use ProxyBank\Controllers\CryptoController;

$app->post('/encrypt', CryptoController::class . ":encrypt");
$app->get('/listBanks', BankController::class . ":listBanks");
