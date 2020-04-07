<?php

include "vendor/autoload.php";

use ProxyBank\Crypto;

$crypto = new Crypto(__DIR__);

function processRequest()
{
    if ($_SERVER["REQUEST_METHOD"] != "POST") {
        http_response_code(405);
        return false;
    }

    if (!isset($_POST["login"]) || !isset($_POST["password"])) {
        http_response_code(400);
        return false;
    }

    $login = $_POST["login"];
    $password = $_POST["password"];

    global $crypto;

    $encrypted = $crypto->encrypt($login . ':' . $password);

    http_response_code(200);
    header('Content-Type: text/plain;charset=UTF-8');
    return base64_encode($encrypted);
}

echo processRequest();
