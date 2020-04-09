<?php


namespace ProxyBank\Controllers;


use ProxyBank\Services\CryptoService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\StreamFactoryInterface;


class CryptoController
{
    /**
     * @Inject
     * @var StreamFactoryInterface
     */
    private $streamFactory;

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

        return $response
            ->withHeader("Content-Type", "text/plain")
            ->withBody($this->streamFactory->createStream(base64_encode($encrypted)));
    }

}
