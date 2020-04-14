<?php


namespace ProxyBank\Controllers;


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

    public function getAuthToken(Request $request, Response $response)
    {
        $params = json_decode((string)$request->getBody(), true);

        $token = null;

        if (isset($params["bankId"])) {
            $token = $this->bankService->getAuthTokenWithBankId($params["bankId"], $params);
        }

        if (isset($params["token"])) {
            $token = $this->bankService->getAuthTokenWithToken($params["token"]);
        }

        if (is_null($token)) {
            return $response->withStatus(400, "bankId or token parameter is required");
        }

        $response->getBody()->write(json_encode($token));
        return $response->withHeader("Content-Type", "application/json");
    }
}
