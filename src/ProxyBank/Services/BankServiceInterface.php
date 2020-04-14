<?php


namespace ProxyBank\Services;


use ProxyBank\Models\Bank;
use ProxyBank\Models\TokenResult;

interface BankServiceInterface
{
    public function getBank(): Bank;

    public function getAuthToken(array $inputs): TokenResult;

    public function fetchTransactions(string $accountId): array;
}
