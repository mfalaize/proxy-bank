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
}
