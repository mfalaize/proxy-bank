<?php


namespace Tests\Unit\Services;


use PHPUnit\Framework\TestCase;
use ProxyBank\Models\Bank;
use ProxyBank\Models\TokenResult;
use ProxyBank\Services\BankService;
use ProxyBank\Services\BankServiceInterface;
use ProxyBank\Services\CryptoService;
use Psr\Container\ContainerInterface;

class BankServiceTest extends TestCase
{
    private $service;

    private $container;

    private $cryptoService;

    protected function setUp(): void
    {
        $this->cryptoService = $this->createMock(CryptoService::class);

        $this->container = $this->createMock(ContainerInterface::class);
        $this->container->expects($this->any())->method("get")->with(CryptoService::class)->willReturn($this->cryptoService);

        $this->service = new BankService($this->container);
    }

    /**
     * @test
     */
    public function constructor_should_instanciate_all_bank_service_implementations()
    {
        $implementations = $this->service->bankImplementations;
        $this->assertEquals(1, sizeof($implementations)); // size should be incremented when adding new bank implementation
        foreach ($implementations as $implementation) {
            $this->assertInstanceOf(BankServiceInterface::class, $implementation);
        }
    }

    /**
     * @test
     */
    public function listAvailableBanks_should_return_sorted_array_of_implemented_banks_name()
    {
        $creditMutuel = new Bank();
        $creditMutuel->id = "1";
        $creditMutuel->name = "Crédit Mutuel";

        $creditAgricole = new Bank();
        $creditAgricole->id = "2";
        $creditAgricole->name = "Crédit Agricole";

        $banks = [$creditMutuel, $creditAgricole];

        $this->service->bankImplementations = array_map(function ($bank) {
            $mock = $this->createMock(BankServiceInterface::class);
            $mock->expects($this->atLeastOnce())
                ->method("getBank")
                ->willReturn($bank);
            return $mock;
        }, $banks);

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
    public function getAuthTokenWithBankId_should_return_token_with_error_message_if_no_bank_implementation_for_bankId()
    {
        $token = $this->service->getAuthTokenWithBankId("null", []);
        $this->assertNull($token->token);
        $this->assertNull($token->completedToken);
        $this->assertEquals("Unknown null bankId", $token->message);
    }

    /**
     * @test
     */
    public function getAuthTokenWithBankId_should_return_bank_implementation_getAuthToken()
    {
        $mock = $this->createMock(BankServiceInterface::class);
        $mock->expects($this->atLeastOnce())->method("getBank")->willReturnCallback(function () {
            $bank = new Bank();
            $bank->id = "credit-mutuel";
            return $bank;
        });
        $mock->expects($this->atLeastOnce())
            ->method("getAuthToken")
            ->with(["test" => "ok"])
            ->willReturnCallback(function ($inputs) {
                $token = new TokenResult();
                $token->message = "A message";
                return $token;
            });

        $this->service->bankImplementations = [174 => $mock]; // 174 simulate the array_filter which does not return necessarily a 0 index array

        $token = $this->service->getAuthTokenWithBankId("credit-mutuel", ["test" => "ok"]);

        $this->assertEquals("A message", $token->message);
    }

    /**
     * @test
     */
    public function getAuthTokenWithToken_should_decrypt_token_and_decode_json_then_call_getAuthTokenWithBankId()
    {
        $this->cryptoService->expects($this->atLeastOnce())
            ->method("decrypt")
            ->with("encryptedToken")
            ->willReturn('{"bankId":"credit-mutuel","test":"ok"}');

        $partialMockService = $this->getMockBuilder(BankService::class)
            ->setConstructorArgs([$this->container])
            ->onlyMethods(["getAuthTokenWithBankId"])
            ->getMock();
        $partialMockService->expects($this->atLeastOnce())
            ->method("getAuthTokenWithBankId")
            ->with("credit-mutuel", ["bankId" => "credit-mutuel", "test" => "ok"])
            ->willReturnCallback(function ($bankId, $inputs) {
                $token = new TokenResult();
                $token->message = "A message";
                return $token;
            });

        $token = $partialMockService->getAuthTokenWithToken("encryptedToken");

        $this->assertEquals("A message", $token->message);
    }
}
