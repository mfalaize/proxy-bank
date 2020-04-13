<?php


namespace Tests\Functional\Bank;


use ProxyBank\Models\Bank;
use ProxyBank\Models\Security\AuthenticationStrategy;
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
        $creditMutuel = new Bank();
        $creditMutuel->id = "1";
        $creditMutuel->name = "Crédit Mutuel";
        $creditMutuel->authenticationStrategy = AuthenticationStrategy::LOGIN_PASSWORD_COOKIE;

        $creditAgricole = new Bank();
        $creditAgricole->id = "2";
        $creditAgricole->name = "Crédit Agricole";
        $creditAgricole->authenticationStrategy = AuthenticationStrategy::LOGIN_PASSWORD_COOKIE;

        $banks = [$creditAgricole, $creditMutuel];

        $this->bankService->expects($this->atLeastOnce())
            ->method("listAvailableBanks")
            ->willReturn($banks);

        $response = $this->app->handle($this->requestFactory->createServerRequest("GET", "/listBanks"));
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("application/json", $response->getHeaderLine("Content-Type"));
        $this->assertEquals('[{"id":"2","name":"Cr\u00e9dit Agricole"},{"id":"1","name":"Cr\u00e9dit Mutuel"}]', (string)$response->getBody());
    }
}
