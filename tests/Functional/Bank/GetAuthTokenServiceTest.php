<?php


namespace Tests\Functional\Bank;


use ProxyBank\Models\Token;
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

        $this->request = $this->requestFactory->createServerRequest("POST", "/bank/token");
    }

    /**
     * @test
     */
    public function should_return_error_400_if_no_bankId_and_no_token()
    {
        $response = $this->app->handle($this->request);

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals("bankId or token parameter is required", $response->getReasonPhrase());
    }

    /**
     * @test
     */
    public function should_call_bankService_getAuthTokenWithBankId_if_bankId_is_present()
    {
        $this->bankService->expects($this->atLeastOnce())
            ->method("getAuthTokenWithBankId")
            ->with("credit-mutuel", ["bankId" => "credit-mutuel", "test" => "ok"])
            ->willReturnCallback(function () {
                $token = new Token();
                $token->message = "A message";
                return $token;
            });

        $this->request->getBody()->write(json_encode([
            "bankId" => "credit-mutuel",
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
    public function should_call_bankService_getAuthTokenWithToken_if_token_is_present()
    {
        $this->bankService->expects($this->atLeastOnce())
            ->method("getAuthTokenWithToken")
            ->with("encryptedToken")
            ->willReturnCallback(function () {
                $token = new Token();
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
