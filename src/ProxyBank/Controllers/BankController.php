<?php


namespace ProxyBank\Controllers;


use ProxyBank\Exceptions\EmptyParsedBodyException;
use ProxyBank\Services\BankService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class BankController
{
    /**
     * @Inject
     * @var BankService
     */
    private $bankService;

    public function listBanks(Request $request, Response $response)
    {
        $list = $this->bankService->listAvailableBanks();
        $response->getBody()->write(json_encode($list));
        return $response->withHeader("Content-Type", "application/json");
    }

    public function getAuthToken(Request $request, Response $response, array $args)
    {
        $params = $request->getParsedBody();

        $token = null;

        if (empty($params)) {
            throw new EmptyParsedBodyException();
        }

        if (isset($params["token"])) {
            $token = $this->bankService->getAuthTokenWithEncryptedToken($args["bankId"], $params["token"]);
        } else {
            $token = $this->bankService->getAuthTokenWithUnencryptedInputs($args["bankId"], $params);
        }

        $response->getBody()->write(json_encode($token));
        return $response->withHeader("Content-Type", "application/json");
    }

    public function listAccounts(Request $request, Response $response, array $args)
    {
        $params = $request->getParsedBody();

        $token = null;

        if (empty($params) || empty($params["token"])) {
            throw new EmptyParsedBodyException();
        }

        $accounts = $this->bankService->listAccounts($args["bankId"], $params["token"]);

        $response->getBody()->write(json_encode($accounts));
        return $response->withHeader("Content-Type", "application/json");
    }
}
