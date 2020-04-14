<?php

use ProxyBank\Controllers\BankController;
use ProxyBank\Controllers\CryptoController;
use Slim\Routing\RouteCollectorProxy;

$app->post('/encrypt', CryptoController::class . ":encrypt");
$app->group("/bank", function (RouteCollectorProxy $group) {
    $group->get("/list", BankController::class . ":listBanks");
    $group->post("/token", BankController::class . ":getAuthToken");
});
