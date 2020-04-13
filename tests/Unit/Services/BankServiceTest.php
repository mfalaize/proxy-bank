<?php


namespace Tests\Unit\Services;


use PHPUnit\Framework\TestCase;
use ProxyBank\Models\Bank;
use ProxyBank\Models\Security\AuthenticationStrategy;
use ProxyBank\Services\BankService;
use ProxyBank\Services\BankServiceInterface;
use Psr\Container\ContainerInterface;

class BankServiceTest extends TestCase
{
    private $service;

    protected function setUp(): void
    {
        $this->service = new BankService(
            $this->createMock(ContainerInterface::class)
        );
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
        $creditMutuel->authenticationStrategy = AuthenticationStrategy::LOGIN_PASSWORD_COOKIE;

        $creditAgricole = new Bank();
        $creditAgricole->id = "2";
        $creditAgricole->name = "Crédit Agricole";
        $creditAgricole->authenticationStrategy = AuthenticationStrategy::LOGIN_PASSWORD_COOKIE;

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
        $this->assertEquals(AuthenticationStrategy::LOGIN_PASSWORD_COOKIE, $availableBanks[0]->authenticationStrategy);

        $this->assertEquals("1", $availableBanks[1]->id);
        $this->assertEquals("Crédit Mutuel", $availableBanks[1]->name);
        $this->assertEquals(AuthenticationStrategy::LOGIN_PASSWORD_COOKIE, $availableBanks[1]->authenticationStrategy);
    }
}
