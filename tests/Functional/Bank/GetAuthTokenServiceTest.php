<?php


namespace Tests\Functional\Bank;


use ProxyBank\Exceptions\UnknownBankIdException;
use ProxyBank\Models\TokenResult;
use ProxyBank\Services\BankService;
use Tests\FunctionalTestCase;
use function GuzzleHttp\Psr7\stream_for;
use function GuzzleHttp\Psr7\uri_for;

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
        $this->request = $this->request->withHeader("Content-Type", "application/json")
            ->withHeader("Accept", "application/json");
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

    /**
     * @test
     */
    public function should_return_error_if_ProxyBankException_occurs()
    {
        $this->bankService->expects($this->atLeastOnce())
            ->method("getAuthTokenWithUnencryptedInputs")
            ->with("null", ["test" => "ok"])
            ->willThrowException(new UnknownBankIdException("null"));

        $response = $this->app->handle($this->request
            ->withUri(uri_for("/bank/null/token"))
            ->withBody(stream_for(json_encode(["test" => "ok"])))
        );

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertEquals('application/json', $response->getHeaderLine("Content-Type"));
        $this->assertJsonStringEqualsJsonString('{"message":"Unknown null bankId"}', (string)$response->getBody());
    }

    /**
     * @test
     */
    public function should_return_error_400_if_content_type_is_not_present()
    {
        $response = $this->app->handle($this->request->withoutHeader("Content-Type"));
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals('application/json', $response->getHeaderLine("Content-Type"));
        $this->assertJsonStringEqualsJsonString('{"message":"Your request is empty or no Content-Type is provided"}', (string)$response->getBody());
    }
}
