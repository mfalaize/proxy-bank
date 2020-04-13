<?php


namespace ProxyBank\Services;


use ProxyBank\Models\Bank;

interface BankServiceInterface
{
    public function getBank(): Bank;

    public function fetchTransactions(string $accountId): array;
}
