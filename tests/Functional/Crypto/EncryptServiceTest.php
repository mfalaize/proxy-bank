<?php

namespace Tests\Functional\Crypto;

use ProxyBank\Services\CryptoService;
use Psr\Http\Message\ServerRequestInterface;
use Tests\FunctionalTestCase;

class EncryptServiceTest extends FunctionalTestCase
{
    /**
     * @var CryptoService
     */
    private $cryptoService;

    /**
     * @var ServerRequestInterface
     */
    private $request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cryptoService = $this->createMock(CryptoService::class);
        $this->container->set(CryptoService::class, $this->cryptoService);

        $this->request = $this->requestFactory->createServerRequest("POST", "/encrypt");
    }

    protected function tearDown(): void
    {
        unset($this->cryptoService);
        parent::tearDown();
    }

    /**
     * @test
     */
    public function should_return_error_400_if_no_login()
    {
        $response = $this->app->handle($this->request->withParsedBody([
            "password" => "myPassword"
        ]));

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals("login and password parameters are required", $response->getReasonPhrase());
    }

    /**
     * @test
     */
    public function should_return_error_400_if_no_password()
    {
        $response = $this->app->handle($this->request->withParsedBody([
            "login" => "myLogin"
        ]));

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals("login and password parameters are required", $response->getReasonPhrase());
    }

    /**
     * @test
     */
    public function should_return_encrypted_login_and_password_in_base64()
    {
        $this->cryptoService->expects($this->atLeastOnce())
            ->method("encrypt")
            ->with("myLogin:myPassword")
            ->willReturn("encryptedData");

        $response = $this->app->handle($this->request->withParsedBody([
            "login" => "myLogin",
            "password" => "myPassword"
        ]));

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("ZW5jcnlwdGVkRGF0YQ==", $response->getBody()->getContents());
    }
}
