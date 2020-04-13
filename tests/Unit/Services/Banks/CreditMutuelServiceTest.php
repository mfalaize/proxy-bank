<?php


namespace Tests\Unit\Services\Banks;


use PHPUnit\Framework\TestCase;
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
        $this->assertEquals(AuthenticationStrategy::LOGIN_PASSWORD_COOKIE, $bank->authenticationStrategy);
    }
}
