<?php

use ProxyBank\Controllers\BankController;
use Slim\Routing\RouteCollectorProxy;

$app->group("/bank", function (RouteCollectorProxy $group) {
    $group->get("/list", BankController::class . ":listBanks");

    $group->group("/{bankId}", function (RouteCollectorProxy $group) {
        $group->post("/token", BankController::class . ":getAuthToken");
    });
});
