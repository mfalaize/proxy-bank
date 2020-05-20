<?php


namespace ProxyBank\Services\Banks\France;


use DateTime;
use DOMDocument;
use DOMXPath;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\SetCookie;
use GuzzleHttp\HandlerStack;
use ProxyBank\Exceptions\AuthenticationException;
use ProxyBank\Exceptions\DSP2TokenExpiredOrInvalidException;
use ProxyBank\Exceptions\RequiredValueException;
use ProxyBank\Exceptions\UnknownAccountIdException;
use ProxyBank\Models\Account;
use ProxyBank\Models\Bank;
use ProxyBank\Models\Input;
use ProxyBank\Models\TokenResult;
use ProxyBank\Models\Transaction;
use ProxyBank\Services\BankServiceInterface;
use ProxyBank\Services\CryptoService;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 *
 * Implementation for Crédit Mutuel.
 *
 * This service implements the API with web scraping method (because existing API is for Crédit Mutuel internal use only
 * and is not documented).
 *
 * Therefore don't worry if you see connection logs on your Crédit Mutuel web interface. :-)
 *
 * @package ProxyBank\Services\Banks\France
 * @link https://www.creditmutuel.fr
 */
class CreditMutuelService implements BankServiceInterface
{

    /**
     * @internal
     */
    const LOGIN_INPUT = "Login";
    /**
     * @internal
     */
    const PASSWORD_INPUT = "Password";
    /**
     * @internal
     */
    const TRANSACTION_ID_INPUT = "transactionId";
    /**
     * @internal
     */
    const VALIDATION_URL_INPUT = "validationUrl";
    /**
     * @internal
     */
    const OTP_HIDDEN_INPUT = "otp_hidden";
    /**
     * @internal
     */
    const COOKIES_INPUT = "cookies";
    /**
     * @internal
     */
    const DSP2_TOKEN_INPUT = "auth_client_state";

    /**
     * @internal
     */
    const DOMAIN = "www.creditmutuel.fr";
    /**
     * @internal
     */
    const BASE_URL = 'https://' . self::DOMAIN;
    /**
     * @internal
     */
    const AUTH_URL = '/fr/authentification.html';
    /**
     * @internal
     */
    const VALIDATION_URL = '/fr/banque/validation.aspx';
    /**
     * @internal
     */
    const OTP_TRANSACTION_STATE_URL = "/fr/banque/async/otp/SOSD_OTP_GetTransactionState.htm";
    /**
     * @internal
     */
    const DOWNLOAD_URL = "/fr/banque/compte/telechargement.cgi";

    /**
     * @internal
     */
    const CSV_FORMAT_EXCEL_XP = 2;
    /**
     * @internal
     */
    const CSV_DATE_FRENCH_FORMAT = 0;
    /**
     * @internal
     */
    const CSV_FIELD_SEPARATOR_SEMICOLON = 0;
    /**
     * @internal
     */
    const CSV_DECIMAL_SEPARATOR_DOT = 1;
    /**
     * @internal
     */
    const CSV_ONE_COLUMN_PER_AMOUNT = 0;

    /**
     * @internal
     */
    public $handlerStack;

    /**
     * @internal
     */
    private $cryptoService;
    /**
     * @internal
     */
    private $logger;

    /**
     * @internal
     */
    public function __construct(CryptoService $cryptoService, LoggerInterface $logger)
    {
        $this->handlerStack = HandlerStack::create();
        $this->cryptoService = $cryptoService;
        $this->logger = $logger;
    }

    /**
     * {@inheritDoc}
     *
     * Bank ID: credit-mutuel
     *
     * Authentication inputs required: Login and Password
     *
     * @return Bank
     */
    public static function getBank(): Bank
    {
        $bank = new Bank();
        $bank->id = "credit-mutuel";
        $bank->name = "Crédit Mutuel";
        $bank->authInputs = [
            new Input(self::LOGIN_INPUT, Input::TYPE_TEXT),
            new Input(self::PASSWORD_INPUT, Input::TYPE_PASSWORD),
        ];
        return $bank;
    }

    /**
     * {@inheritDoc}
     *
     * ### First step
     *
     * First call with Login and Password as authentication inputs. We log in against the website authentication page.
     * Then Crédit Mutuel needs your smartphone validation (in the Crédit Mutuel application) so we return immediately
     * a {@link TokenResult} with a {@link TokenResult::$token}, {@link TokenResult::$completedToken} to false and a
     * {@link TokenResult::$message} to indicates that we need your validation.
     *
     * ### Second step
     *
     * You have validated your authentication on your smartphone. You call again this method with the previously generated
     * {@link TokenResult::$token} which contains cookies, your login/password and information about the validation transaction.
     * Now we can generate the auth_client_state cookie, which is the DSP2 cookie valid for 90 days. We generate a new
     * {@link TokenResult::$token} with the auth_client_state cookie and login/password and now we have the complete token.
     *
     * @param array $inputs
     * @return TokenResult
     * @throws DSP2TokenExpiredOrInvalidException
     * @throws RequiredValueException
     */
    public function getAuthToken(array $inputs): TokenResult
    {
        if (!isset($inputs[self::LOGIN_INPUT])) {
            throw new RequiredValueException(self::LOGIN_INPUT);
        }

        if (!isset($inputs[self::PASSWORD_INPUT])) {
            throw new RequiredValueException(self::PASSWORD_INPUT);
        }

        if (isset($inputs[self::TRANSACTION_ID_INPUT])) {
            return $this->processTransactionState($inputs);
        }

        $client = $this->processAuthentication($inputs);
        return $this->getAuthTokenWithLoginSuccess($client, $inputs);
    }

    /**
     * @internal
     */
    private function processAuthentication(array $inputs): Client
    {
        $client = $this->buildClientHttp();
        $hasDSP2Token = isset($inputs[self::DSP2_TOKEN_INPUT]);

        if ($hasDSP2Token) {
            $this->logger->debug("Adding DSP2 token to cookies");
            $client->getConfig("cookies")->setCookie(new SetCookie([
                "Domain" => self::DOMAIN,
                "Name" => self::DSP2_TOKEN_INPUT,
                "Value" => $inputs[self::DSP2_TOKEN_INPUT]
            ]));
        }

        $this->logger->debug("Authentication: POST request to " . self::AUTH_URL);
        $response = $client->post(self::AUTH_URL, [
            "form_params" => ([
                "_cm_user" => $inputs[self::LOGIN_INPUT],
                "_cm_pwd" => $inputs[self::PASSWORD_INPUT],
                "flag" => "password"
            ])
        ]);

        if ($response->getStatusCode() != 302) {
            $this->processLoginFailed($response);
        } else if ($hasDSP2Token && $response->getHeaderLine("Location") == self::BASE_URL . self::VALIDATION_URL) {
            throw new DSP2TokenExpiredOrInvalidException(self::getBank()->name);
        }

        $this->logger->debug("Authentication success");

        return $client;
    }

    /**
     * @internal
     */
    private function processTransactionState(array $inputs): TokenResult
    {
        $client = $this->buildClientHttp();
        foreach ($inputs[self::COOKIES_INPUT] as $cookie) {
            $this->logger->debug("Setting cookie {cookie} from decrypted token", ["cookie" => $cookie["Name"]]);
            $client->getConfig("cookies")->setCookie(new SetCookie($cookie));
        }

        $this->logger->debug("Verify auth factor state: POST request to " . self::OTP_TRANSACTION_STATE_URL);
        $response = $client->post(self::OTP_TRANSACTION_STATE_URL, [
            "form_params" => ([
                "transactionId" => $inputs[self::TRANSACTION_ID_INPUT]
            ])
        ]);

        $xml = new DOMDocument();
        $xml->loadXML($response->getBody());
        $status = $xml->getElementsByTagName("transactionState")->item(0)->textContent;

        $token = new TokenResult();
        if ($status == "PENDING") {
            $token->message = "En attente de votre validation...";
        } else if ($status == "CANCELLED") {
            $token->message = "La transaction a été annulée";
        } else if ($status == "VALIDATED") {
            $this->logger->debug("Auth factor state is validated: POST request to " . self::VALIDATION_URL);
            // This call to the validation URL fills out the client cookie jar with the valid dsp2 token
            $client->post($inputs[self::VALIDATION_URL_INPUT], [
                "form_params" => ([
                    "otp_hidden" => $inputs[self::OTP_HIDDEN_INPUT],
                    "_FID_DoValidate.x" => 0,
                    "_FID_DoValidate.y" => 0
                ])
            ]);

            $this->logger->debug("Retrieve DSP2 cookie");
            $dsp2Token = $client->getConfig("cookies")->getCookieByName("auth_client_state")->getValue();

            $tokenJson = json_encode([
                self::LOGIN_INPUT => $inputs[self::LOGIN_INPUT],
                self::PASSWORD_INPUT => $inputs[self::PASSWORD_INPUT],
                self::DSP2_TOKEN_INPUT => $dsp2Token
            ]);

            $token->token = $this->cryptoService->encrypt($tokenJson);
            $token->completedToken = true;
        }
        return $token;
    }

    /**
     * @internal
     */
    private function getAuthTokenWithLoginSuccess(Client $client, array $inputs): TokenResult
    {
        $this->logger->debug("Validation: GET request to " . self::VALIDATION_URL);
        $response = $client->get(self::VALIDATION_URL);
        $htmlString = (string)$response->getBody();

        $matches = [];
        preg_match("/.*transactionId: '(.+)'/", $htmlString, $matches);
        $transactionId = $matches[1];

        $html = new DOMDocument();
        $html->loadHTML($htmlString, LIBXML_NOWARNING | LIBXML_NOERROR);

        $validationUrl = $html->getElementById("C:P:F")->getAttribute("action");

        $xpath = new DOMXPath($html);
        $otpHidden = $xpath->query("//input[@name='otp_hidden']")->item(0)->getAttribute("value");

        $cookies = $client->getConfig("cookies")->toArray();

        $message = $html->getElementById("inMobileAppMessage")->textContent;
        $message = trim($message);
        $message = str_replace("  ", "", $message);

        $tokenJson = json_encode([
            self::LOGIN_INPUT => $inputs[self::LOGIN_INPUT],
            self::PASSWORD_INPUT => $inputs[self::PASSWORD_INPUT],
            self::TRANSACTION_ID_INPUT => $transactionId,
            self::VALIDATION_URL_INPUT => $validationUrl,
            self::OTP_HIDDEN_INPUT => $otpHidden,
            self::COOKIES_INPUT => $cookies
        ]);

        $token = new TokenResult();
        $token->token = $this->cryptoService->encrypt($tokenJson);
        $token->completedToken = false;
        $token->message = $message;

        $this->logger->debug("Validation: Generate incomplete token which needs additional auth factor");

        return $token;
    }

    /**
     * @internal
     */
    private function processLoginFailed(ResponseInterface $response)
    {
        $this->logger->debug("Authentication failed. Attempting to retrieve error message");

        $html = new DOMDocument();
        $html->loadHTML($response->getBody(), LIBXML_NOWARNING | LIBXML_NOERROR);
        $identDivs = $html->getElementById("ident")->getElementsByTagName("div");
        $errorMessage = null;
        foreach ($identDivs as $identDiv) {
            $cssClasses = preg_split("/ /", $identDiv->getAttribute("class"));
            if (in_array("err", $cssClasses)) {
                $errorMessage = $identDiv->textContent;
                break;
            }
        }

        throw new AuthenticationException($errorMessage);
    }

    /**
     * @internal
     */
    private function buildClientHttp()
    {
        return new Client([
            "base_uri" => self::BASE_URL,
            "allow_redirects" => false,
            "cookies" => true,
            "handler" => $this->handlerStack
        ]);
    }

    /**
     * {@inheritDoc}
     *
     * For this, we go to the Download page of your customer area and parse HTML to list available accounts.
     *
     * @param array $inputs
     * @return array
     * @throws DSP2TokenExpiredOrInvalidException
     */
    public function listAccounts(array $inputs): array
    {
        $client = $this->processAuthentication($inputs);
        $data = $this->extractAccountsFromDownloadPage($client);

        $accounts = [];
        foreach ($data["accounts"] as $accountData) {
            $account = new Account();
            $account->id = $accountData["accountId"];
            $account->name = $accountData["accountName"];
            $accounts[] = $account;
        }

        return $accounts;
    }

    /**
     * {@inheritDoc}
     *
     * For this, we go to the Download page of your customer area and generate CSV file for the chosen account.
     * Then we parse the CSV and convert it to a {@link Transaction} list.
     *
     * @param string $accountId
     * @param array $inputs
     * @return array
     * @throws DSP2TokenExpiredOrInvalidException
     * @throws UnknownAccountIdException
     */
    public function fetchTransactions(string $accountId, array $inputs): array
    {
        $client = $this->processAuthentication($inputs);
        $data = $this->extractAccountsFromDownloadPage($client);

        $accountCheckboxName = null;

        foreach ($data["accounts"] as $account) {
            if ($accountId == $account["accountId"]) {
                $accountCheckboxName = $account["accountCheckboxName"];
                break;
            }
        }

        if (is_null($accountCheckboxName)) {
            throw new UnknownAccountIdException($accountId);
        }

        $this->logger->debug("Download CSV to fetch transactions: POST request to " . $data["downloadCsvUrl"]);
        $response = $client->post($data["downloadCsvUrl"], [
            "form_params" => ([
                "data_formats_selected" => "csv",
                "data_formats_options_csv_fileformat" => self::CSV_FORMAT_EXCEL_XP,
                "data_formats_options_csv_dateformat" => self::CSV_DATE_FRENCH_FORMAT,
                "data_formats_options_csv_fieldseparator" => self::CSV_FIELD_SEPARATOR_SEMICOLON,
                "data_formats_options_csv_amountcolnumber" => self::CSV_ONE_COLUMN_PER_AMOUNT,
                "data_formats_options_csv_decimalseparator" => self::CSV_DECIMAL_SEPARATOR_DOT,
                $accountCheckboxName => "on",
                "_FID_DoDownload.x" => 0,
                "_FID_DoValidate.y" => 0
            ])
        ]);

        return $this->convertCsvToTransactions((string)$response->getBody());
    }

    /**
     * @internal
     */
    private function extractAccountsFromDownloadPage(Client $authenticatedClient): array
    {
        $this->logger->debug("List accounts: GET request to " . self::DOWNLOAD_URL);
        $response = $authenticatedClient->get(self::DOWNLOAD_URL);

        $html = new DOMDocument();
        $html->loadHTML($response->getBody(), LIBXML_NOWARNING | LIBXML_NOERROR);

        $downloadCsvUrl = $html->getElementById('P1:F')->getAttribute('action');

        $accountNodes = $html->getElementById('account-table')->getElementsByTagName('label');
        $accountCheckboxName = null;
        $accounts = [];

        foreach ($accountNodes as $accountNode) {
            $splitSpaces = preg_split('/ /', $accountNode->textContent, 4);
            $accountId = join('', array_slice($splitSpaces, 0, 3));
            $accountName = $splitSpaces[3];
            $accountCheckboxId = $accountNode->getAttribute("for");
            $accountCheckboxName = $html->getElementById($accountCheckboxId)->getAttribute("name");
            $accounts[] = [
                "accountId" => $accountId,
                "accountName" => $accountName,
                "accountCheckboxName" => $accountCheckboxName
            ];
        }

        return [
            "downloadCsvUrl" => $downloadCsvUrl,
            "accounts" => $accounts
        ];
    }

    /**
     * @internal
     */
    private function convertCsvToTransactions(string $csv): array
    {
        $lineSeparator = "\n";
        $fieldSeparator = ';';
        strtok($csv, $lineSeparator);

        $transactions = array();

        $line = strtok($lineSeparator);

        while ($line !== false) {
            $fields = preg_split('/' . $fieldSeparator . '/', $line);
            $transaction = new Transaction();
            $transaction->date = DateTime::createFromFormat('d/m/Y H:i:s', $fields[0] . '00:00:00');
            $transaction->description = $fields[3];
            $transaction->amount = $fields[2];
            $transaction->accountBalance = trim($fields[4]);
            $transactions[] = $transaction;

            $line = strtok($lineSeparator);
        }

        $this->logger->debug("Retrieving " . sizeof($transactions) . " transactions");

        return $transactions;
    }
}
