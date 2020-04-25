<?php


namespace ProxyBank\Services\Banks;


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

class CreditMutuelService implements BankServiceInterface
{

    const LOGIN_INPUT = "Login";
    const PASSWORD_INPUT = "Password";
    const TRANSACTION_ID_INPUT = "transactionId";
    const VALIDATION_URL_INPUT = "validationUrl";
    const OTP_HIDDEN_INPUT = "otp_hidden";
    const COOKIES_INPUT = "cookies";
    const DSP2_TOKEN_INPUT = "auth_client_state";

    const DOMAIN = "www.creditmutuel.fr";
    const BASE_URL = 'https://' . self::DOMAIN;
    const AUTH_URL = '/fr/authentification.html';
    const VALIDATION_URL = '/fr/banque/validation.aspx';
    const OTP_TRANSACTION_STATE_URL = "/fr/banque/async/otp/SOSD_OTP_GetTransactionState.htm";
    const DOWNLOAD_URL = "/fr/banque/compte/telechargement.cgi";

    const CSV_FORMAT_EXCEL_XP = 2;
    const CSV_DATE_FRENCH_FORMAT = 0;
    const CSV_FIELD_SEPARATOR_SEMICOLON = 0;
    const CSV_DECIMAL_SEPARATOR_DOT = 1;
    const CSV_ONE_COLUMN_PER_AMOUNT = 0;

    public $handlerStack;

    private $cryptoService;

    public function __construct(CryptoService $cryptoService)
    {
        $this->handlerStack = HandlerStack::create();
        $this->cryptoService = $cryptoService;
    }

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
        return $this->processLoginSuccess($client, $inputs);
    }

    private function processAuthentication(array $inputs): Client
    {
        $client = $this->buildClientHttp();
        $hasDSP2Token = isset($inputs[self::DSP2_TOKEN_INPUT]);

        if ($hasDSP2Token) {
            $client->getConfig("cookies")->setCookie(new SetCookie([
                "Domain" => self::DOMAIN,
                "Name" => self::DSP2_TOKEN_INPUT,
                "Value" => $inputs[self::DSP2_TOKEN_INPUT]
            ]));
        }

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

        return $client;
    }

    private function processTransactionState(array $inputs): TokenResult
    {
        $client = $this->buildClientHttp();
        foreach ($inputs[self::COOKIES_INPUT] as $cookie) {
            $client->getConfig("cookies")->setCookie(new SetCookie($cookie));
        }

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
            // This call to the validation URL fills out the client cookie jar with the valid dsp2 token
            $client->post($inputs[self::VALIDATION_URL_INPUT], [
                "form_params" => ([
                    "otp_hidden" => $inputs[self::OTP_HIDDEN_INPUT],
                    "_FID_DoValidate.x" => 0,
                    "_FID_DoValidate.y" => 0
                ])
            ]);

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

    private function processLoginSuccess(Client $client, array $inputs): TokenResult
    {
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
        return $token;
    }

    private function processLoginFailed(ResponseInterface $response)
    {
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

    private function buildClientHttp()
    {
        return new Client([
            "base_uri" => self::BASE_URL,
            "allow_redirects" => false,
            "cookies" => true,
            "handler" => $this->handlerStack
        ]);
    }

    public function listAccounts(array $inputs): array
    {
        $client = $this->processAuthentication($inputs);

        $response = $client->get(self::DOWNLOAD_URL);

        $html = new DOMDocument();
        $html->loadHTML($response->getBody(), LIBXML_NOWARNING | LIBXML_NOERROR);

        $accountNodes = $html->getElementById('account-table')->getElementsByTagName('label');
        $accounts = [];
        foreach ($accountNodes as $accountNode) {
            $splitSpaces = preg_split('/ /', $accountNode->textContent, 4);
            $accountId = join('', array_slice($splitSpaces, 0, 3));
            $accountName = $splitSpaces[3];

            $account = new Account();
            $account->id = $accountId;
            $account->name = $accountName;
            $accounts[] = $account;
        }

        return $accounts;
    }

    public function fetchTransactions(string $accountId, array $inputs): array
    {
        $client = $this->processAuthentication($inputs);

        $response = $client->get(self::DOWNLOAD_URL);

        $html = new DOMDocument();
        $html->loadHTML($response->getBody(), LIBXML_NOWARNING | LIBXML_NOERROR);

        $downloadCsvUrl = $html->getElementById('P:F')->getAttribute('action');

        $accountNodes = $html->getElementById('account-table')->getElementsByTagName('label');
        $accountCheckboxName = null;

        foreach ($accountNodes as $accountNode) {
            $splitSpaces = preg_split('/ /', $accountNode->textContent, 4);
            $accountNodeId = join('', array_slice($splitSpaces, 0, 3));

            if ($accountId == $accountNodeId) {
                $accountCheckboxId = $accountNode->getAttribute("for");
                $accountCheckboxName = $html->getElementById($accountCheckboxId)->getAttribute("name");
                break;
            }
        }

        if (is_null($accountCheckboxName)) {
            throw new UnknownAccountIdException($accountId);
        }

        $response = $client->post($downloadCsvUrl, [
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
            $transaction->accountBalance = $fields[4];
            $transactions[] = $transaction;

            $line = strtok($lineSeparator);
        }

        return $transactions;
    }
}
