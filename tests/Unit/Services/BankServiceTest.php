<?php


namespace Tests\Unit\Services;


use DI\NotFoundException;
use PHPUnit\Framework\TestCase;
use ProxyBank\Container;
use ProxyBank\Models\Bank;
use ProxyBank\Models\TokenResult;
use ProxyBank\Services\BankService;
use ProxyBank\Services\CryptoService;
use ProxyBank\Services\IntlService;

class BankServiceTest extends TestCase
{
    private $service;

    private $container;

    private $cryptoService;

    private $intlService;

    protected function setUp(): void
    {
        $this->cryptoService = $this->createMock(CryptoService::class);
        $this->container = $this->createMock(Container::class);
        $this->intlService = new IntlService();
        $this->intlService->setLocale("en_US");

        $this->service = new BankService($this->container, $this->cryptoService, $this->intlService);
    }

    /**
     * @test
     */
    public function listAvailableBanks_should_return_sorted_array_of_implemented_banks_name()
    {
        $this->container->expects($this->atLeastOnce())
            ->method("getBankImplementations")
            ->willReturn([
                new TestCreditMutuel(),
                new TestCreditAgricole()
            ]);

        $availableBanks = $this->service->listAvailableBanks();
        $this->assertInstanceOf(Bank::class, $availableBanks[0]);

        $this->assertEquals("2", $availableBanks[0]->id);
        $this->assertEquals("Crédit Agricole", $availableBanks[0]->name);

        $this->assertEquals("1", $availableBanks[1]->id);
        $this->assertEquals("Crédit Mutuel", $availableBanks[1]->name);
    }

    /**
     * @test
     */
    public function getAuthTokenWithUnencryptedInputs_should_return_token_with_error_message_if_no_bank_implementation_for_bankId()
    {
        $this->container->expects($this->atLeastOnce())
            ->method("get")
            ->with("null")
            ->willThrowException(new NotFoundException());

        $token = $this->service->getAuthTokenWithUnencryptedInputs("null", null);
        $this->assertNull($token->token);
        $this->assertNull($token->completedToken);
        $this->assertEquals("Unknown null bankId", $token->message);
    }

    /**
     * @test
     */
    public function getAuthTokenWithUnencryptedInputs_should_return_bank_implementation_getAuthToken()
    {
        $mock = $this->createMock(TestCreditMutuel::class);
        $mock->expects($this->atLeastOnce())
            ->method("getAuthToken")
            ->with(["test" => "ok"])
            ->willReturnCallback(function ($inputs) {
                $token = new TokenResult();
                $token->message = "A message";
                return $token;
            });

        $this->container->expects($this->atLeastOnce())
            ->method("get")
            ->willReturn($mock);

        $token = $this->service->getAuthTokenWithUnencryptedInputs("credit-mutuel", ["test" => "ok"]);

        $this->assertEquals("A message", $token->message);
    }

    /**
     * @test
     */
    public function getAuthTokenWithEncryptedToken_should_decrypt_token_and_decode_json_then_call_getAuthTokenWithUnencryptedInputs()
    {
        $this->cryptoService->expects($this->atLeastOnce())
            ->method("decrypt")
            ->with("encryptedToken")
            ->willReturn('{"test":"ok"}');

        $partialMockService = $this->getMockBuilder(BankService::class)
            ->setConstructorArgs([$this->container, $this->cryptoService, $this->intlService])
            ->onlyMethods(["getAuthTokenWithUnencryptedInputs"])
            ->getMock();
        $partialMockService->expects($this->atLeastOnce())
            ->method("getAuthTokenWithUnencryptedInputs")
            ->with("credit-mutuel", ["test" => "ok"])
            ->willReturnCallback(function ($bankId, $inputs) {
                $token = new TokenResult();
                $token->message = "A message";
                return $token;
            });

        $token = $partialMockService->getAuthTokenWithEncryptedToken("credit-mutuel", "encryptedToken");

        $this->assertEquals("A message", $token->message);
    }
}

class TestCreditMutuel
{

    public static function getBank(): Bank
    {
        $creditMutuel = new Bank();
        $creditMutuel->id = "1";
        $creditMutuel->name = "Crédit Mutuel";
        return $creditMutuel;
    }

    public function getAuthToken(): TokenResult
    {
    }
}

class TestCreditAgricole
{

    public static function getBank(): Bank
    {
        $creditAgricole = new Bank();
        $creditAgricole->id = "2";
        $creditAgricole->name = "Crédit Agricole";
        return $creditAgricole;
    }
}
