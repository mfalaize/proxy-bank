<?php


namespace ProxyBank\Services;


interface BankServiceInterface
{
    public function getBankName(): string;

    public function fetchTransactions(string $accountId): array;
}
