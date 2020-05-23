<?php


namespace Tests\Functional\Exceptions;


use ProxyBank\Exceptions\AuthenticationException;
use ProxyBank\Exceptions\EmptyParsedBodyException;
use ProxyBank\Exceptions\ExpiredAuthenticationException;
use ProxyBank\Exceptions\InvalidTokenException;
use ProxyBank\Exceptions\RequiredValueException;
use ProxyBank\Exceptions\UnknownAccountIdException;
use ProxyBank\Exceptions\UnknownBankIdException;
use ProxyBank\Services\BankService;
use Tests\FunctionalTestCase;

class ProxyBankExceptionsTest extends FunctionalTestCase
{
    private $bankService;

    private $request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bankService = $this->createMock(BankService::class);
        $this->container->set(BankService::class, $this->bankService);

        $this->request = $this->requestFactory->createServerRequest("GET", "/bank/list")
            ->withHeader("Accept", "application/json");
    }

    /**
     * @test
     */
    public function AuthenticationException_should_return_401_with_display_given_message()
    {
        $this->bankService->expects($this->atLeastOnce())
            ->method("listAvailableBanks")
            ->willThrowException(new AuthenticationException("My Error Message"));

        $response = $this->app->handle($this->request);

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString('{"message":"My Error Message"}', (string)$response->getBody());
    }

    /**
     * @test
     */
    public function EmptyParsedBody_should_return_400()
    {
        $this->bankService->expects($this->atLeastOnce())
            ->method("listAvailableBanks")
            ->willThrowException(new EmptyParsedBodyException());

        $response = $this->app->handle($this->request);

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString('{"message":"Your request is empty or no Content-Type is provided"}', (string)$response->getBody());
    }

    /**
     * @test
     */
    public function InvalidTokenException_should_return_400()
    {
        $this->bankService->expects($this->atLeastOnce())
            ->method("listAvailableBanks")
            ->willThrowException(new InvalidTokenException());

        $response = $this->app->handle($this->request);

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString('{"message":"Invalid token. Please authenticate again to generate a new token"}', (string)$response->getBody());
    }

    /**
     * @test
     */
    public function RequiredValueException_should_return_400()
    {
        $this->bankService->expects($this->atLeastOnce())
            ->method("listAvailableBanks")
            ->willThrowException(new RequiredValueException("InputName"));

        $response = $this->app->handle($this->request);

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString('{"message":"InputName value is required"}', (string)$response->getBody());
    }

    /**
     * @test
     */
    public function UnknownBankIdException_should_return_404()
    {
        $this->bankService->expects($this->atLeastOnce())
            ->method("listAvailableBanks")
            ->willThrowException(new UnknownBankIdException("MyBankId"));

        $response = $this->app->handle($this->request);

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString('{"message":"Unknown MyBankId bankId"}', (string)$response->getBody());
    }

    /**
     * @test
     */
    public function UnknownAccountId_should_return_404()
    {
        $this->bankService->expects($this->atLeastOnce())
            ->method("listAvailableBanks")
            ->willThrowException(new UnknownAccountIdException("0123456"));

        $response = $this->app->handle($this->request);

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString('{"message":"Unknown 0123456 accountId"}', (string)$response->getBody());
    }

    /**
     * @test
     */
    public function expiredAuthenticationException_should_return_401()
    {
        $this->bankService->expects($this->atLeastOnce())
            ->method("listAvailableBanks")
            ->willThrowException(new ExpiredAuthenticationException("Crédit Mutuel"));

        $response = $this->app->handle($this->request);

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString('{"message":"Expired authentication for bank Cr\u00e9dit Mutuel"}', (string)$response->getBody());
    }

    /**
     * @test
     */
    public function Errors_should_return_500_with_custom_message()
    {
        $this->bankService->expects($this->atLeastOnce())
            ->method("listAvailableBanks")
            ->willReturnCallback(function () {
                $var = null;
                return $var->whatever();
            });

        $response = $this->app->handle($this->request);

        $this->assertEquals(500, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString('{"message":"An unexpected error has occurred. Please contact the website administrator."}', (string)$response->getBody());
    }
}
