<?php

use ProxyBank\Controllers\BankController;
use Slim\Routing\RouteCollectorProxy;

$app->group("/bank", function (RouteCollectorProxy $group) {
    $group->get("/list", BankController::class . ":listBanks");
    $group->post("/token", BankController::class . ":getAuthToken");
});
