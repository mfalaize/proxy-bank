<?php


namespace Tests\Functional\Bank;


use ProxyBank\Models\Account;
use ProxyBank\Services\BankService;
use Tests\FunctionalTestCase;

class ListAccountsServiceTest extends FunctionalTestCase
{
    private $bankService;

    private $request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bankService = $this->createMock(BankService::class);
        $this->container->set(BankService::class, $this->bankService);

        $this->request = $this->requestFactory->createServerRequest("POST", "/bank/credit-mutuel/account/list");
        $this->request = $this->request->withHeader("Content-Type", "application/json")
            ->withHeader("Accept", "application/json");
    }

    /**
     * @test
     */
    public function should_return_bankService_listAccounts()
    {
        $this->bankService->expects($this->atLeastOnce())
            ->method("listAccounts")
            ->with("credit-mutuel", "encryptedToken")
            ->willReturnCallback(function () {
                $account1 = new Account();
                $account1->id = "123456";
                $account1->name = "Account 1";

                $account2 = new Account();
                $account2->id = "123457";
                $account2->name = "Account 2";

                return [$account1, $account2];
            });

        $this->request->getBody()->write(json_encode([
            "token" => "encryptedToken"
        ]));

        $response = $this->app->handle($this->request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("application/json", $response->getHeaderLine("Content-Type"));
        $this->assertEquals('[{"id":"123456","name":"Account 1"},{"id":"123457","name":"Account 2"}]', (string)$response->getBody());
    }

    /**
     * @test
     */
    public function should_return_400_if_token_is_not_present()
    {
        $this->bankService->expects($this->never())
            ->method("listAccounts");

        $response = $this->app->handle($this->request);

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString('{"message":"Your request is empty or no Content-Type is provided"}', (string)$response->getBody());
    }
}
