<?php


namespace ProxyBank\Services;


use ProxyBank\Models\Bank;
use ProxyBank\Models\Token;

interface BankServiceInterface
{
    public function getBank(): Bank;

    public function getAuthToken(array $inputs): Token;

    public function fetchTransactions(string $accountId): array;
}
