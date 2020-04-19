<?php


namespace Tests\Unit;


use PHPUnit\Framework\TestCase;
use ProxyBank\Container;
use ProxyBank\Services\BankServiceInterface;

class ContainerTest extends TestCase
{
    /**
     * @test
     */
    public function getBankImplementations_should_return_all_entries_that_implements_BankServiceInterface()
    {
        $partialMockContainer = $this->getMockBuilder(Container::class)
            ->onlyMethods(["getKnownEntryNames", "get"])
            ->getMock();

        $partialMockContainer->expects($this->atLeastOnce())
            ->method("getKnownEntryNames")
            // 174 and 167 simulate the array_filter which does not return necessarily a 0 index array
            ->willReturn([174 => "credit-mutuel", 167 => "credit-agricole"]);

        $creditMutuelService = $this->createMock(BankServiceInterface::class);
        $creditAgricoleService = $this->createMock(BankServiceInterface::class);

        $partialMockContainer->expects($this->atLeastOnce())
            ->method("get")
            ->willReturnCallback(function (string $id) use ($creditMutuelService, $creditAgricoleService) {
                if ($id == "credit-mutuel") {
                    return $creditMutuelService;
                }
                if ($id == "credit-agricole") {
                    return $creditAgricoleService;
                }
                return null;
            });

        $this->assertEquals([$creditMutuelService, $creditAgricoleService], $partialMockContainer->getBankImplementations());
    }
}
