<?php


namespace ProxyBank\Services;


use ProxyBank\Models\Account;
use ProxyBank\Models\Bank;
use ProxyBank\Models\TokenResult;
use ProxyBank\Models\Transaction;

/**
 * Interface for all Bank implementations.
 *
 * @package ProxyBank\Services
 */
interface BankServiceInterface
{
    /**
     * Get information about the bank.
     *
     * The information contains especially the ProxyBank bankId (to know which URL you have to use) and the authentication
     * inputs you have to give.
     *
     * @return Bank
     */
    public static function getBank(): Bank;

    /**
     * Generate a ProxyBank encrypted authentication token for the bank.
     *
     * This token contains all information your bank needs to authenticate against it. This is AES 256 encrypted with
     * ProxyBank secret key so that only ProxyBank can decrypt it.
     *
     * You have to store this token on your side because ProxyBank does not store it.
     *
     * Please note that you can receive from this method a partial token, because your bank may demand additional auth
     * information (multi factor authentication).
     *
     * @param array $inputs Clear authentication inputs
     * @return TokenResult The token
     */
    public function getAuthToken(array $inputs): TokenResult;

    /**
     * List available accounts in the bank.
     *
     * @param array $inputs Clear authentication inputs (previously decrypted by ProxyBank).
     * @return array List of {@link Account}
     */
    public function listAccounts(array $inputs): array;

    /**
     * Fetch all available transactions for the bank account.
     *
     * @param string $accountId The bank account id given by {@link listAccounts} method.
     * @param array $inputs Clear authentication inputs (previously decrypted by ProxyBank).
     * @return array List of {@link Transaction}
     */
    public function fetchTransactions(string $accountId, array $inputs): array;
}
