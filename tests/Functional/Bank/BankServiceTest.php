<?php


namespace Tests\Functional\Bank;


use ProxyBank\Services\BankService;
use Tests\FunctionalTestCase;

class BankServiceTest extends FunctionalTestCase
{

    private $bankService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bankService = $this->createMock(BankService::class);
        $this->container->set(BankService::class, $this->bankService);
    }

    /**
     * @test
     */
    public function listBanks_should_return_implemented_ordered_bank_names_in_json()
    {
        $this->bankService->expects($this->atLeastOnce())
            ->method("listAvailableBankNames")
            ->willReturn(["Crédit Agricole", "Crédit Mutuel"]);

        $response = $this->app->handle($this->requestFactory->createServerRequest("GET", "/listBanks"));
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("application/json", $response->getHeaderLine("Content-Type"));
        $this->assertEquals("[\"Cr\u00e9dit Agricole\",\"Cr\u00e9dit Mutuel\"]", $response->getBody());
    }
}
