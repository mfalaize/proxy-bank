<?php


namespace ProxyBank\Services;


use ProxyBank\Models\Bank;
use ProxyBank\Models\TokenResult;

interface BankServiceInterface
{
    public static function getBank(): Bank;

    public function getAuthToken(array $inputs): TokenResult;

    public function listAccounts(array $inputs): array;

    public function fetchTransactions(string $accountId, array $inputs): array;
}
