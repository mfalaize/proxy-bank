<?php


namespace ProxyBank\Services;


use ProxyBank\Exceptions\AuthenticationException;
use ProxyBank\Exceptions\ExpiredAuthenticationException;
use ProxyBank\Exceptions\RequiredValueException;
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
     * This token contains all information your bank needs to authenticate against it. The token has to be a json object
     * encrypted with ProxyBank secret key (AES 256) so that only ProxyBank can decrypt it, and base64 encoded.
     * The encryption and base64 encoding processes are implemented by the {@link CryptoService::encrypt()} method.
     *
     * The client has to store this token on its side because ProxyBank does not store it.
     *
     * Please note that this method may return a partial token, because your bank may demand additional auth information
     * (e.g. multi factor authentication). In this case, the {@link TokenResult::$partialToken} property is set to `true`.
     * If your bank implementation needs it, you have to handle in this method all the different steps the client
     * will have to execute to get the fully complete token. For the additional steps, the client will always give the partial
     * token as inputs (and you will get it decrypted as the method param), so you can put in the partial token all
     * information you will require for the next step.
     *
     * @param array $inputs Clear authentication inputs
     * @return TokenResult The token
     * @throws AuthenticationException If the authentication information is invalid
     * @throws RequiredValueException If a required value in the inputs param is missing
     */
    public function getAuthToken(array $inputs): TokenResult;

    /**
     * List available accounts in the bank.
     *
     * List only accounts available for fetching transactions.
     *
     * @param array $inputs Clear authentication inputs (previously decrypted by ProxyBank).
     * @return array List of {@link Account}
     * @throws ExpiredAuthenticationException If the authentication information is expired
     */
    public function listAccounts(array $inputs): array;

    /**
     * Fetch all available transactions for the bank account.
     *
     * @param string $accountId The bank account id given by {@link listAccounts} method.
     * @param array $inputs Clear authentication inputs (previously decrypted by ProxyBank).
     * @return array List of {@link Transaction}
     * @throws ExpiredAuthenticationException If the authentication information is expired
     */
    public function fetchTransactions(string $accountId, array $inputs): array;
}
