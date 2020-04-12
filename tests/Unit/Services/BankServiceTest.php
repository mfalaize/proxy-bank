<?php


namespace Tests\Unit\Services;


use PHPUnit\Framework\TestCase;
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
    public function listAvailableBankNames_should_return_ordered_array_of_implemented_banks_name()
    {
        $bankNames = ["Crédit Mutuel", "Crédit Agricole"];

        $this->service->bankImplementations = array_map(function ($bankName) {
            $mock = $this->createMock(BankServiceInterface::class);
            $mock->expects($this->atLeastOnce())
                ->method("getBankName")
                ->willReturn($bankName);
            return $mock;
        }, $bankNames);

        $this->assertEquals(["Crédit Agricole", "Crédit Mutuel"], $this->service->listAvailableBankNames());
    }
}
