<?php


namespace ProxyBank\Controllers;


use ProxyBank\Services\BankService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\StreamFactoryInterface;

class BankController
{

    /**
     * @Inject
     * @var StreamFactoryInterface
     */
    private $streamFactory;

    /**
     * @Inject
     * @var BankService
     */
    private $bankService;

    public function listBanks(Request $request, Response $response)
    {
        $list = $this->bankService->listAvailableBankNames();
        return $response->withBody($this->streamFactory->createStream(json_encode($list)));
    }
}
