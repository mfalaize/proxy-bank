<?php


namespace Tests\Unit\Services\Banks;


use PHPUnit\Framework\TestCase;
use ProxyBank\Models\Input;
use ProxyBank\Models\Security\AuthenticationStrategy;
use ProxyBank\Services\Banks\CreditMutuelService;
use Psr\Container\ContainerInterface;

class CreditMutuelServiceTest extends TestCase
{
    private $service;

    protected function setUp(): void
    {
        $this->service = new CreditMutuelService(
            $this->createMock(ContainerInterface::class)
        );
    }

    /**
     * @test
     */
    public function getBank_should_return_config_for_the_bank()
    {
        $bank = $this->service->getBank();
        $this->assertEquals("credit-mutuel", $bank->id); // Should NEVER change
        $this->assertEquals("Crédit Mutuel", $bank->name);
        $this->assertEquals("Login", $bank->authInputs[0]->name);
        $this->assertEquals(Input::TYPE_TEXT, $bank->authInputs[0]->type);
        $this->assertEquals("Password", $bank->authInputs[1]->name);
        $this->assertEquals(Input::TYPE_PASSWORD, $bank->authInputs[1]->type);
        $this->assertEquals(2, sizeof($bank->authInputs));
    }
}
