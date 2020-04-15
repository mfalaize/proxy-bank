<?php


namespace ProxyBank\Services\Banks;


use DOMDocument;
use DOMXPath;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\SetCookie;
use GuzzleHttp\HandlerStack;
use ProxyBank\Models\Bank;
use ProxyBank\Models\Input;
use ProxyBank\Models\TokenResult;
use ProxyBank\Services\BankServiceInterface;
use ProxyBank\Services\CryptoService;
use Psr\Container\ContainerInterface;
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

    public $handlerStack;

    private $cryptoService;

    public function __construct(ContainerInterface $container)
    {
        $this->handlerStack = HandlerStack::create();
        $this->cryptoService = $container->get(CryptoService::class);
    }

    public function getBank(): Bank
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
            $token = new TokenResult();
            $token->message = self::LOGIN_INPUT . " value is required";
            return $token;
        }

        if (!isset($inputs[self::PASSWORD_INPUT])) {
            $token = new TokenResult();
            $token->message = self::PASSWORD_INPUT . " value is required";
            return $token;
        }

        if (isset($inputs[self::TRANSACTION_ID_INPUT])) {
            return $this->processTransactionState(
                $inputs[self::LOGIN_INPUT],
                $inputs[self::PASSWORD_INPUT],
                $inputs[self::TRANSACTION_ID_INPUT],
                $inputs[self::VALIDATION_URL_INPUT],
                $inputs[self::OTP_HIDDEN_INPUT],
                $inputs[self::COOKIES_INPUT]
            );
        }

        return $this->processAuthentication($inputs[self::LOGIN_INPUT], $inputs[self::PASSWORD_INPUT]);
    }

    private function processAuthentication(string $login, string $password): TokenResult
    {
        $client = $this->buildClientHttp();

        $response = $client->post(self::AUTH_URL, [
            "form_params" => ([
                "_cm_user" => $login,
                "_cm_pwd" => $password,
                "flag" => "password"
            ])
        ]);

        if ($response->getStatusCode() == 302) {
            return $this->processLoginSuccess($client, $login, $password);
        } else {
            return $this->processLoginFailed($response);
        }
    }

    private function processTransactionState(string $login, string $password,
                                             string $transactionId, string $validationUrl,
                                             string $otpToken, array $cookies): TokenResult
    {
        $client = $this->buildClientHttp();
        foreach ($cookies as $cookie) {
            $client->getConfig("cookies")->setCookie(new SetCookie($cookie));
        }

        $response = $client->post(self::OTP_TRANSACTION_STATE_URL, [
            "form_params" => ([
                "transactionId" => $transactionId
            ])
        ]);

        $xml = new DOMDocument();
        $xml->loadXML($response->getBody());
        $status = $xml->getElementsByTagName("transactionState")->item(0)->textContent;

        $token = new TokenResult();
        if ($status == "PENDING") {
            $token->message = "En attente de votre validation...";
        } else if ($status == "VALIDATED") {
            // This call to the validation URL fill out the client cookie jar with the valid dsp2 token
            $client->post($validationUrl, [
                "form_params" => ([
                    "otp_hidden" => $otpToken,
                    "_FID_DoValidate.x" => 0,
                    "_FID_DoValidate.y" => 0
                ])
            ]);

            $dsp2Token = $client->getConfig("cookies")->getCookieByName("auth_client_state")->getValue();

            $tokenJson = json_encode([
                "bankId" => $this->getBank()->id,
                self::LOGIN_INPUT => $login,
                self::PASSWORD_INPUT => $password,
                self::DSP2_TOKEN_INPUT => $dsp2Token
            ]);

            $token->token = $this->cryptoService->encrypt($tokenJson);
            $token->completedToken = true;
        }
        // TODO process cancelled transaction
        return $token;
    }

    private function processLoginSuccess(Client $client, string $login, string $password): TokenResult
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
            "bankId" => $this->getBank()->id,
            self::LOGIN_INPUT => $login,
            self::PASSWORD_INPUT => $password,
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

    private function processLoginFailed(ResponseInterface $response): TokenResult
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

        $token = new TokenResult();
        $token->message = $errorMessage;
        return $token;
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

    public function fetchTransactions(string $accountId): array
    {
        return [];
    }
}
