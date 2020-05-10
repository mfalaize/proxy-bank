<?php

use ProxyBank\Controllers\BankController;
use ProxyBank\Controllers\OpenApiController;
use ProxyBank\Controllers\ResourcesController;
use Slim\Routing\RouteCollectorProxy;

$app->group("/bank", function (RouteCollectorProxy $group) {
    $group->get("/list", BankController::class . ":listBanks");

    $group->group("/{bankId}", function (RouteCollectorProxy $group) {
        $group->post("/token", BankController::class . ":getAuthToken");

        $group->group("/account", function (RouteCollectorProxy $group) {
            $group->post("/list", BankController::class . ":listAccounts");
            $group->post("/lastTransactions", BankController::class . ":fetchTransactions");
        });
    });
});

$app->get("/", OpenApiController::class . ":swaggerUi");
$app->get("/swagger.json", OpenApiController::class . ":swaggerJson");

$app->get("/{root:assets|docs}/{path:.*}.{type}", ResourcesController::class . ":getResource");
