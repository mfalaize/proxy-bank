<?php


namespace ProxyBank\Services\Banks;


use ProxyBank\Models\Bank;
use ProxyBank\Models\Security\AuthenticationStrategy;
use ProxyBank\Services\BankServiceInterface;
use Psr\Container\ContainerInterface;

class CreditMutuelService implements BankServiceInterface
{

    public function __construct(ContainerInterface $container)
    {
    }

    public function getBank(): Bank
    {
        $bank = new Bank();
        $bank->id = "credit-mutuel";
        $bank->name = "Crédit Mutuel";
        $bank->authenticationStrategy = AuthenticationStrategy::LOGIN_PASSWORD_COOKIE;
        return $bank;
    }

    public function fetchTransactions(string $accountId): array
    {
        return [];
    }
}
