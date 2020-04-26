<?php


namespace Tests\Functional\Bank;


use DateTime;
use ProxyBank\Models\Transaction;
use ProxyBank\Services\BankService;
use Tests\FunctionalTestCase;

class FetchTransactionsServiceTest extends FunctionalTestCase
{
    private $bankService;

    private $request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bankService = $this->createMock(BankService::class);
        $this->container->set(BankService::class, $this->bankService);

        $this->request = $this->requestFactory->createServerRequest("POST", "/bank/credit-mutuel/account/lastTransactions");
        $this->request = $this->request->withHeader("Content-Type", "application/json")
            ->withHeader("Accept", "application/json");
    }

    /**
     * @test
     */
    public function should_return_bankService_fetchTransactions()
    {
        $this->bankService->expects($this->atLeastOnce())
            ->method("fetchTransactions")
            ->with("credit-mutuel", "MyAccountId", "encryptedToken")
            ->willReturnCallback(function () {
                $transaction1 = new Transaction();
                $transaction1->date = new DateTime("2020-04-02");
                $transaction1->description = "My transaction 1";
                $transaction1->amount = "230.54";
                $transaction1->accountBalance = "1230.54";

                $transaction2 = new Transaction();
                $transaction2->date = new DateTime("2020-04-03");
                $transaction2->description = "My transaction 2";
                $transaction2->amount = "-230.54";
                $transaction2->accountBalance = "1000.00";

                return [$transaction1, $transaction2];
            });

        $this->request->getBody()->write(json_encode([
            "accountId" => "MyAccountId",
            "token" => "encryptedToken"
        ]));

        $response = $this->app->handle($this->request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("application/json", $response->getHeaderLine("Content-Type"));
        $this->assertEquals('[{"date":"2020-04-02","description":"My transaction 1","amount":"230.54","accountBalance":"1230.54"},{"date":"2020-04-03","description":"My transaction 2","amount":"-230.54","accountBalance":"1000.00"}]', (string)$response->getBody());
    }

    /**
     * @test
     */
    public function should_return_400_if_token_is_not_present()
    {
        $this->bankService->expects($this->never())
            ->method("fetchTransactions");

        $this->request->getBody()->write(json_encode([
            "accountId" => "MyAccountId"
        ]));

        $response = $this->app->handle($this->request);

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString('{"message":"Your request is empty or no Content-Type is provided"}', (string)$response->getBody());
    }

    /**
     * @test
     */
    public function should_return_400_if_accountId_is_not_present()
    {
        $this->bankService->expects($this->never())
            ->method("fetchTransactions");

        $this->request->getBody()->write(json_encode([
            "token" => "encryptedToken"
        ]));

        $response = $this->app->handle($this->request);

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString('{"message":"accountId value is required"}', (string)$response->getBody());
    }
}
