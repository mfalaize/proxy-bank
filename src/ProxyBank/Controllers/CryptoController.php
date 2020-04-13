<?php


namespace ProxyBank\Controllers;


use ProxyBank\Services\CryptoService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;


class CryptoController
{
    /**
     * @Inject
     * @var CryptoService
     */
    private $cryptoService;

    public function encrypt(Request $request, Response $response)
    {
        $params = $request->getParsedBody();

        if (!isset($params["login"]) || !isset($params["password"])) {
            return $response->withStatus(400, "login and password parameters are required");
        }

        $encrypted = $this->cryptoService->encrypt($params["login"] . ":" . $params["password"]);

        $response->getBody()->write(base64_encode($encrypted));

        return $response
            ->withHeader("Content-Type", "text/plain");
    }

}
