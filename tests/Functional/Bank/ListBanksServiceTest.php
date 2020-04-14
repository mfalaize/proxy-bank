<?php


namespace Tests\Functional\Bank;


use ProxyBank\Models\Bank;
use ProxyBank\Models\Input;
use ProxyBank\Services\BankService;
use Tests\FunctionalTestCase;

class ListBanksServiceTest extends FunctionalTestCase
{

    private $bankService;

    private $request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bankService = $this->createMock(BankService::class);
        $this->container->set(BankService::class, $this->bankService);

        $this->request = $this->requestFactory->createServerRequest("GET", "/bank/list");
    }

    /**
     * @test
     */
    public function should_return_implemented_ordered_bank_names_in_json()
    {
        $creditMutuel = new Bank();
        $creditMutuel->id = "1";
        $creditMutuel->name = "Crédit Mutuel";
        $creditMutuel->authInputs = [
            new Input("Login", Input::TYPE_TEXT),
            new Input("Password", Input::TYPE_PASSWORD),
        ];

        $creditAgricole = new Bank();
        $creditAgricole->id = "2";
        $creditAgricole->name = "Crédit Agricole";
        $creditAgricole->authInputs = [
            new Input("Login", Input::TYPE_TEXT),
            new Input("Password", Input::TYPE_PASSWORD),
        ];

        $banks = [$creditAgricole, $creditMutuel];

        $this->bankService->expects($this->atLeastOnce())
            ->method("listAvailableBanks")
            ->willReturn($banks);

        $response = $this->app->handle($this->request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("application/json", $response->getHeaderLine("Content-Type"));
        $this->assertEquals('[{"id":"2","name":"Cr\u00e9dit Agricole","authInputs":[{"name":"Login","type":"text"},{"name":"Password","type":"password"}]},{"id":"1","name":"Cr\u00e9dit Mutuel","authInputs":[{"name":"Login","type":"text"},{"name":"Password","type":"password"}]}]', (string)$response->getBody());
    }
}
