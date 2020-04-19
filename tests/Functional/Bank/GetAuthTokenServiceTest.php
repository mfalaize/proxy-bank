<?php


namespace Tests\Functional\Bank;


use ProxyBank\Models\TokenResult;
use ProxyBank\Services\BankService;
use Tests\FunctionalTestCase;

class GetAuthTokenServiceTest extends FunctionalTestCase
{

    private $bankService;

    private $request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bankService = $this->createMock(BankService::class);
        $this->container->set(BankService::class, $this->bankService);

        $this->request = $this->requestFactory->createServerRequest("POST", "/bank/credit-mutuel/token");
        $this->request = $this->request->withHeader("Content-Type", "application/json");
    }

    /**
     * @test
     */
    public function should_call_bankService_getAuthTokenWithUnencryptedInputs_if_token_is_not_present()
    {
        $this->bankService->expects($this->atLeastOnce())
            ->method("getAuthTokenWithUnencryptedInputs")
            ->with("credit-mutuel", ["test" => "ok"])
            ->willReturnCallback(function () {
                $token = new TokenResult();
                $token->message = "A message";
                return $token;
            });

        $this->request->getBody()->write(json_encode([
            "test" => "ok"
        ]));
        $response = $this->app->handle($this->request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('application/json', $response->getHeaderLine("Content-Type"));
        $this->assertEquals('{"message":"A message"}', (string)$response->getBody());
    }

    /**
     * @test
     */
    public function should_call_bankService_getAuthTokenWithEncryptedToken_if_token_is_present()
    {
        $this->bankService->expects($this->atLeastOnce())
            ->method("getAuthTokenWithEncryptedToken")
            ->with("credit-mutuel", "encryptedToken")
            ->willReturnCallback(function () {
                $token = new TokenResult();
                $token->token = "anotherEncryptedToken";
                $token->completedToken = true;
                $token->message = "OK";
                return $token;
            });

        $this->request->getBody()->write(json_encode([
            "token" => "encryptedToken"
        ]));
        $response = $this->app->handle($this->request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('application/json', $response->getHeaderLine("Content-Type"));
        $this->assertEquals('{"token":"anotherEncryptedToken","completedToken":true,"message":"OK"}', (string)$response->getBody());
    }
}
