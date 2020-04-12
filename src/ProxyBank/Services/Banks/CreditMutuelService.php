<?php


namespace ProxyBank\Services\Banks;


use ProxyBank\Services\BankServiceInterface;
use Psr\Container\ContainerInterface;

class CreditMutuelService implements BankServiceInterface
{

    public function __construct(ContainerInterface $container)
    {
    }

    public function getBankName(): string
    {
        return "Crédit Mutuel";
    }

    public function fetchTransactions(string $accountId): array
    {
        return [];
    }
}
